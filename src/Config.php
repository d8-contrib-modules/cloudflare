<?php
/**
 * @file
 * Contains \Drupal\cloudflare\Config.
 */

namespace Drupal\cloudflare;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;

/**
 * Invalidation methods for CloudFlare.
 */
class Config implements CloudFlareConfigInterface {
  /*
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /*
   * @var string
   */
  protected $apiKey;

  /*
   * @var string
   */
  protected $email;

  /*
   * @var string
   */
  protected $zone;

  /*
   * @var \CloudFlarePhpSdk\ApiEndpoints\ZoneApi
   */
  protected $zoneApi;

  /*
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * CloudFlareInvalidator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerInterface $logger) {
    $this->config = $config->get('cloudflare.settings');
    $this->logger = $logger;

    $this->apiKey = $this->config->get('apikey');
    $this->email = $this->config->get('email');
    $this->zoneApi = new ZoneApi($this->apiKey, $this->email);
    if ($this->hasValidApiCredentials()) {
      $this->zone = $this->getCurrentZoneId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getZoneApi() {
    return $this->zoneApi;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentZoneId() {
    $has_zone_in_cmi = !is_null($this->config->get('zone'));

    // If this is a multi-zone cloudflare account and a zone has been set.
    if ($has_zone_in_cmi) {
      return $this->config->get('zone');
    }

    if (!is_null($this->zone)) {
      return $this->zone;
    }

    // If there is no zone set and the account only has a single zone.
    try {
      $zones_from_api = $this->zoneApi->listZones();
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("CloudFlare: Unable to list zones." . $e->getMessage(), 'error');
      $this->logger->error($e->getMessage());
      return NULL;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("CloudFlare: Unable to list zones." . $e->getMessage(), 'error');
      $this->logger->error($e->getMessage());
      return NULL;
    }

    $num_zones_from_api = count($zones_from_api);
    $is_single_zone_cloudflare_account = $num_zones_from_api == 1;
    if ($is_single_zone_cloudflare_account) {
      return $zones_from_api[0]->getZoneId();
    }

    // If the zone has multiple accounts and none is specified in CMI we cannot
    // move forward.
    if (!$is_single_zone_cloudflare_account) {
      $link_to_settings = '/admin/config/services/cloudflare/zone';
      $message = t('No default zone has been entered for CloudFlare. Please go <a href="@link_to_settings">here</a> to set.', ['@link_to_settings' => $link_to_settings]);

      drupal_set_message($message, 'error');
      $this->logger->error($message);
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidApiCredentials() {
    if (!isset($this->apiKey)) {
      $link_to_settings = '/admin/config/services/cloudflare';
      $message = t('No valid credentials have been entered for CloudFlare. Please go <a href="@link_to_settings">here</a> to set them.', ['@link_to_settings' => $link_to_settings]);

      drupal_set_message($message, 'error');
      $this->logger->error($message);
      return FALSE;
    }

    return TRUE;
  }

}
