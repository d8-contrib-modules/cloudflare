<?php

/**
 * @file
 * Contains \Drupal\cloudflare\CacheTagsInvalidator.
 */

namespace Drupal\cloudflare;

use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;
use Drupal\cloudflare\EventSubscriber\CloudFlareCacheTagHeaderGenerator;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Cache tags invalidator implementation that invalidates CloudFlare.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    // When either an extension (module/theme) is (un)installed, purge
    // everything.
    if (in_array('config:core.extension', $tags)) {
      // @todo Purge everything. Blocked on https://github.com/d8-contrib-modules/cloudflare/issues/16.
      return;
    }

    // Also invalidate the cache tags as hashes, to automatically also work for
    // responses that exceed CloudFlare's Cache-Tag header limit.
    $hashes = CloudFlareCacheTagHeaderGenerator::cacheTagsToHashes($tags);
    $tags = Cache::mergeTags($tags, $hashes);

    $this->purgeTags($tags);
  }

  /**
   * Purges cache tags on CloudFlare.
   *
   * @todo Once https://github.com/d8-contrib-modules/cloudflare/issues/16 is
   *   done, this should disappear.
   *
   * @param string[] $tags
   *   The list of tags for which to invalidate cache items.
   */
  public function purgeTags(array $tags) {
    $config = \Drupal::config('cloudflare.settings');
    $api_key = $config->get('apikey');
    $email = $config->get('email');
    $zone = $config->get('zone');

    try {
      $this->zoneApi = new ZoneApi($api_key, $email);

      // @todo rethink how to handle cloudflare zones in Drupal.
      if (is_null($zone)) {
        $zones = $this->zoneApi->listZones();
        $zone = $zones[0]->getZoneId();
      }

      $this->zoneApi->purgeTags($zone, $tags);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to clear zone cache. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to clear zone cache. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    // If no exceptions have been thrown then the request has been successful.
  }

}
