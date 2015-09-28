<?php

/**
 * @file
 * Contains \Drupal\cloudflare\CloudFlarePurger.
 */

namespace Drupal\cloudflare;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;


/**
 * Invalidation methods for CloudFlare.
 */
class Purger {
  /*
   * @var \Drupal\cloudflare\Config
   */
  protected $config;

  /*
   * @var \Drupal\cloudflare\Config
   */
  protected $zoneApi;

  /*
   * @var \Drupal\cloudflare\Config
   */
  protected $zone;

  /**
   * CloudFlare Purger constructor.
   *
   * @param \Drupal\cloudflare\Config $config
   *   CloudFlare config object.
   */
  public function __construct(Config &$config) {
    $this->config = $config;
    $this->zoneApi = $config->getZoneApi();

    if ($this->config->hasValidApiCredentials()) {
      $this->zone = $this->config->getCurrentZoneId();
    }
  }

  /**
   * Invalidates tags at CloudFlare edge.
   *
   * @param array $tags
   *   String array of tag names to invalidate.
   */
  public function invalidateByTags(array $tags) {
    if (!$this->config->hasValidApiCredentials()) {
      return;
    }

    try {
      $this->zoneApi->purgeTags($this->zone, $tags);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to clear zone cache." . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to clear zone cache." . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }

    // If no exceptions have been thrown then the request has been successful.
  }

  /**
   * Invalidates paths at CloudFlare edge.
   *
   * NOTE: wildcards are not currently supported by the CloudFlare API.
   *
   * @param array $paths
   *   String array of paths to invalidate.
   */
  public function invalidateByPath(array $paths) {
    if (!$this->config->hasValidApiCredentials()) {
      return;
    }

    try {
      $this->zoneApi->purgeIndividualFiles($this->zone, $paths);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to clear zone cache." . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to clear zone cache." . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }
  }

  /**
   * Invalidates entire zone at CloudFlare edge.  Use cautiously.
   */
  public function invalidateZone() {
    if (!$this->config->hasValidApiCredentials()) {
      return;
    }

    try {
      $this->zoneApi->purgeAllFiles($this->zone);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to clear zone cache." . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to clear zone cache." . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }
  }

}
