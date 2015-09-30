<?php

/**
 * @file
 * Contains \Drupal\cloudflare\Plugin\PurgePurger\VarnishTagPurger.
 */

namespace Drupal\cloudflare\Plugin\PurgePurger;
use Drupal\Core\Cache\Cache;
use Drupal\purge\Plugin\PurgeInvalidation\Tag;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Invalidation\PluginInterface as Invalidation;
use Drupal\cloudflare\EventSubscriber\CloudFlareCacheTagHeaderGenerator;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;

/**
 * Varnish cache tags purger.
 *
 * Requires the associated Varnish server to have a VCL configured to accept
 * BAN requests with a X-Drupal-Cache-Tags-Banned header.
 * See the README for details on the required VCL configuration.
 *
 * Drupal sends X-Drupal-Cache-Tags headers. Varnish caches Drupal's responses
 * with those headers. This purger sends X-Drupal-Cache-Tags-Banned headers (the
 * same header name, but with '-Banned' suffixed) to the desired Varnish
 * instances to invalidate the responses with those cache tags.
 *
 * @PurgePurger(
 *   id = "cloudflare_tag",
 *   label = @Translation("CloudFlare (cache tags)"),
 *   description = @Translation("Cache tags purger for CloudFlare."),
 *   configform = "Drupal\cloudflare\Form\CloudFlareAdminSettingsForm",
 *   multi_instance = TRUE,
 * )
 */
class CloudFlareTagPurger extends PluginBase implements PluginInterface {
  // @todo move this to API.
  const TIMEOUT = 1.0;
  const TAGS_PER_CALL = 30;
  const TAG_API_PURGES_PER_DAY = 200;

  /**
   * {@inheritdoc}
   */
  public function invalidate(Invalidation $invalidation) {
    $this->invalidateMultiple([$invalidation]);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    // @todo figure out why this is happening.
    if (count($invalidations) == 0) {
      \Drupal::logger('cloudflare')->warning("No Invalidations?  That's odd." . print_r($invalidations, TRUE));
      return;
    }

    // For now - until Purge only sends supported invalidation objects - mark
    // anything besides a tag as immediately failed.
    if (!$invalidations[0] instanceof Tag) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(Invalidation::STATE_FAILED);
        $this->numberFailed += 1;
      }
    }

    $chunks = array_chunk($invalidations, self::TAGS_PER_CALL);
    foreach ($chunks as $chunk) {
      $this->purgeChunk($chunk);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Need to determine if anything actually needs to be done here.
   */
  public function delete() {
  }

  /**
   * Purges a chunk of tags.
   *
   * Integration point between purge and CloudFlareAPI.  Purge requires state
   * tracking on each item purged.  This function provides that accounting and
   * calls CloudflareApi.
   *
   * CloudFlare only allows us to purge 30 tags at once.
   *
   * @param array $invalidations
   *   Chunk of purge module invalidation objects to purge via CloudFlare.
   */
  private function purgeChunk(array &$invalidations) {
    // This method is unfortunately a bit verbose due to the fact that we
    // need to update the purge states as we proceed.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(Invalidation::STATE_PURGING);
      $this->numberPurging++;
      $tags[] = $invalidation->getExpression();
    }

    try {
      $this->invalidateTags($tags);
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(Invalidation::STATE_PURGED);

        $this->numberPurging--;
        $this->numberPurged++;
      }
    }

    catch (Exception $e) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(Invalidation::STATE_FAILED);
      }
      $this->numberFailed++;
    }
  }

  /**
   * Invalidate tags using temporary hash mechanism.
   *
   * Remove this wrapper once CloudFlare supports 16k headers and call
   * purgeTagsAtCloudFlare directly.
   *
   * @param array $tags
   *   The list of tags to invalidate.
   */
  public function invalidateTags(array $tags) {
    // When either an extension (module/theme) is (un)installed, purge
    // everything.
    if (in_array('config:core.extension', $tags)) {
      // @todo Purge everything. Blocked on
      //   https://github.com/d8-contrib-modules/cloudflare/issues/16.
      return;
    }
    // Also invalidate the cache tags as hashes, to automatically also work for
    // responses that exceed CloudFlare's Cache-Tag header limit.
    $hashes = CloudFlareCacheTagHeaderGenerator::cacheTagsToHashes($tags);
    $tags = Cache::mergeTags($tags, $hashes);
    $this->purgeTagsAtCloudFlare($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    // Take the HTTP timeout configured, add 10% margin and round up to seconds.
    return (int) ceil(self::TIMEOUT * 1.1);
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
  public function purgeTagsAtCloudFlare(array $tags) {
    $config = \Drupal::config('cloudflare.settings');
    $api_key = $config->get('apikey');
    $email = $config->get('email');
    $zone = $config->get('zone');

    // If the module is not yet configured, don't attempt to purge.
    // @todo Improve the rest of the architecture of this module so this check
    //   is not necessary anymore.
    if (!isset($api_key)) {
      return;
    }

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
