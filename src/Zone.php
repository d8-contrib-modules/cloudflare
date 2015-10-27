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
   * {@inheritdoc}
   */
  public static function create(ConfigFactoryInterface $config, LoggerInterface $logger, CloudFlareStateInterface $state) {
    $cf_config = $config->get('cloudflare.settings');
    $api_key = $cf_config->get('apikey');
    $email = $cf_config->get('email');

    $zoneapi = new ZoneApi($api_key, $email);

    return new static(
      $config,
      $logger,
      $state,
      $zoneapi
    );
  }

  /**
   * Zone constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   CloudFlare config object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param \CloudFlarePhpSdk\ApiEndpoints\ZoneApi $zone_api
   *   ZoneApi instance for accessing api.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerInterface $logger, CloudFlareStateInterface $state, ZoneApi $zone_api) {
    $this->config = $config->get('cloudflare.settings');
    $this->logger = $logger;
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->zone = $this->config->get('zone');

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
