<?php

namespace Drupal\cloudflare;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\cloudflare\Exception\ComposerDependencyException;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\Exceptions\CloudFlareException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

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
   * Checks that the composer dependencies for CloudFlare are met.
   *
   * @var \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface
   */
  protected $cloudFlareComposerDependenciesCheck;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ConfigFactoryInterface $config_factory, LoggerInterface $logger, CacheBackendInterface $cache, CloudFlareStateInterface $state, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $config = $config_factory->get('cloudflare.settings');
    $api_key = $config->get('apikey');
    $email = $config->get('email');

    // If someone has not correctly installed composer here is where we need to
    // handle it to prevent PHP error.
    try {
      $check_interface->assert();
      $zoneapi = new ZoneApi($api_key, $email);
    }
    catch (ComposerDependencyException $e) {
      $zoneapi = NULL;
    }

    return new static(
      $config_factory,
      $logger,
      $cache,
      $state,
      $zoneapi,
      $check_interface
    );
  }

  /**
   * Zone constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param \CloudFlarePhpSdk\ApiEndpoints\ZoneApi|null $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks that composer dependencies are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, CacheBackendInterface $cache, CloudFlareStateInterface $state, $zone_api, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $this->config = $config_factory->get('cloudflare.settings');
    $this->logger = $logger;
    $this->cache = $cache;
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->zone = $this->config->get('zone');
    $this->validCredentials = $this->config->get('valid_credentials');
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
  }

  /**
   * {@inheritdoc}
   */
  public function getZoneSettings() {
    $this->cloudFlareComposerDependenciesCheck->assert();

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
    $this->cloudFlareComposerDependenciesCheck->assert();

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
    $this->cloudFlareComposerDependenciesCheck->assert();
    $zones = [];
    $cid = 'cloudflare_zone_listing';
    try {

      if ($cached = $this->cache->get($cid)) {
        return $cached->data;
      }

      else {
        $zones = $this->zoneApi->listZones();

        // @todo come up with a better approach.
        $num_pages = ceil(count($zones) / ZoneApi::MAX_ITEMS_PER_PAGE);
        for ($i = 0; $i < $num_pages; $i++) {
          $this->state->incrementApiRateCount();
        }

        $this->cache->set($cid, $zones, time() + 60 * 5, ['cloudflare_zone']);
      }

    }
    catch (CloudFlareException $e) {
      $this->logger->error($e->getMessage());
      throw $e;
    }
    return $zones;
  }

  /**
   * {@inheritdoc}
   */
  public static function assertValidCredentials($apikey, $email, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state) {
    $composer_dependency_check->assert();
    $zone_api_direct = new ZoneApi($apikey, $email);

    try {
      $zones = $zone_api_direct->listZones();
    }
    catch (Exception $e) {
      throw $e;
    }
    finally {
      $state->incrementApiRateCount();
    }

  }

}
