<?php

/**
 * @file
 * Contains \Drupal\cloudflare\Zone.
 */

namespace Drupal\cloudflare;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;

/**
 * Zone methods for CloudFlare.
 */
class Zone implements CloudflareZoneInterface {
  /*
   * @var \Drupal\cloudflare\Config
   */
  protected $config;

  /*
   * @var \CloudFlarePhpSdk\ApiEndpoints\ZoneApi
   */
  protected $zoneApi;

  /*
   * @var string
   */
  protected $zone;

  /**
   * Zone constructor.
   *
   * @param \Drupal\cloudflare\Config $config
   *   CloudFlare config object.
   */
  public function __construct(Config $config) {
    $this->config = $config;
    $this->zoneApi = $config->getZoneApi();

    if ($this->config->hasValidApiCredentials()) {
      $this->zone = $this->config->getCurrentZoneId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getZoneSettings() {
    if (!$this->config->hasValidApiCredentials()) {
      return NULL;
    }

    try {
      return $this->zoneApi->getZoneSettings($this->zone);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message(t('Unable to get zone settings.') . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return NULL;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message(t('Unable to get zone settings.') . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateZone(ZoneSettings $zone_settings) {
    if (!$this->config->hasValidApiCredentials()) {
      return;
    }

    try {
      $this->zoneApi->updateZone($zone_settings);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message(t('Unable to update zone settings.') . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message(t('Unable to update zone settings.') . $e->getMessage(), 'error');
      $this->config->getLogger()->error($e->getMessage());
      return;
    }
  }

}
