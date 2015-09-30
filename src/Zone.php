<?php

/**
 * @file
 * Contains \Drupal\cloudflare\Zone.
 */

namespace Drupal\cloudflare;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\Exceptions\CloudFlareException;
use Psr\Log\LoggerInterface;

/**
 * Zone methods for CloudFlare.
 */
class Zone implements CloudFlareZoneInterface {
  use StringTranslationTrait;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * ZoneApi object for interfacing with CloudFlare Php Sdk.
   *
   * @var \CloudFlarePhpSdk\ApiEndpoints\ZoneApi
   */
  protected $zoneApi;

  /**
   * The current cloudflare ZoneId.
   *
   * @var string
   */
  protected $zone;

  /**
   * Flag for valid credentials.
   *
   * @var bool
   */
  protected $validCredentials;

  /**
   * Zone constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   CloudFlare config object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerInterface $logger, CloudFlareStateInterface $state) {
    $this->config = $config->get('cloudflare.settings');
    $this->logger = $logger;
    $this->state = $state;

    $api_key = $this->config->get('apikey');
    $email = $this->config->get('email');
    $this->zone = $this->config->get('zone');

    $this->zoneApi = new ZoneApi($api_key, $email);
    $this->validCredentials = $this->config->get('valid_credentials');
  }

  /**
   * {@inheritdoc}
   */
  public function getZoneSettings() {
    if (!$this->validCredentials) {
      return NULL;
    }

    try {
      $settings = $this->zoneApi->getZoneSettings($this->zone);
      $this->state->incrementApiRateCount();

      return $settings;
    }

    catch (CloudFlareException $e) {
      $this->logger->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateZoneSettings(ZoneSettings $zone_settings) {
    if (!$this->validCredentials) {
      return;
    }

    try {
      $this->zoneApi->updateZone($zone_settings);
      $this->state->incrementApiRateCount();
    }

    catch (CloudFlareException $e) {
      $this->logger->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function listZones() {
    $zones = [];
    try {
      $zones = $this->zoneApi->listZones();
      $this->state->incrementApiRateCount();
    }

    catch (CloudFlareException $e) {
      $this->logger->error($e->getMessage());
      throw $e;
    }
    return $zones;
  }

}
