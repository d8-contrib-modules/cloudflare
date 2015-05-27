<?php

/**
 * @file
 */

namespace Drupal\cloudflare\CloudFlarePhpSdk;

use Drupal\cloudflare\CloudFlarePhpSdk\ApiTypes;
use Drupal\cloudflare;
use GuzzleHttp;


/**
 * Provides functionality for reading and manipulating CloudFlare zones via API.
 */
class CloudFlareZoneEndPoint extends CloudFlareAPI{

  // Zone security levels.
  const ZONE_SECURITY_OFF = 'essentially_off';
  const ZONE_SECURITY_LOW = 'low';
  const ZONE_SECURITY_MEDIUM = 'medium';
  const ZONE_SECURITY_HIGH = 'high';
  const ZONE_SECURITY_UNDERATTACK = 'under_attack';

  // Zone cache levels.
  const ZONE_CACHE_SIMPLIFIED = 'simplified';
  const ZONE_CACHE_BASIC = 'basic';
  const ZONE_CACHE_AGGRESSIVE = 'aggressive';

  // Polish settings.
  const ZONE_POLISH_OFF = 'off';
  const ZONE_POLISH_LOSSLESS = 'lossless';
  const ZONE_POLISH_LOSSY = 'lossy';

  /**
   * Constructor for new instance of CloudFlareZoneEndPoint.
   *
   * @param string $apikey
   *   Cloud flare API key.
   * @param NULL_string $email
   *   Email add
   */
  function __construct($apikey, $email = NULL) {
    parent::__construct($apikey, $email = NULL);
  }

  /**
   * Retrieves a listing of Zones from CloudFlare.
   *
   * @return array
   *   A array of CloudFlareZones objects from the current CloudFlare account.
   *
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function listZones() {
    $request_path = 'zones';
    $this->makeRequest(self::REQUEST_TYPE_GET, $request_path);

    $parsed_zones = [];
    foreach ($this->getResponse()->json()['result'] as $zone) {
      $parsed_zones[] = new CloudFlareZone($zone);
    }
    return $parsed_zones;
  }

  /**
   * @param $zone_id
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function purgeAllFiles($zone_id) {
    $request_path = strtr('zones/:identifier/purge_cache', [':identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_DELETE, $request_path, ['purge_everything' => TRUE]);
  }

  /**
   * @param $zone_id
   * @param array $files
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function purgeIndividualFiles($zone_id, array $files) {
    $request_path = strtr('zones/:identifier/purge_cache', [':identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_DELETE, $request_path, array('files' => $files));
  }

  /**
   * @param $zone_id
   * @param $zone_level
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function setSecurityLevel($zone_id, $zone_level) {
    $request_path = strtr('zones/:zone_identifier/settings/security_level', [':zone_identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $request_path, ['value' => $zone_level]);
  }

  /**
   * @param $zone_id
   * @param $cache_level
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function setCacheLevel($zone_id, $cache_level) {
    $request_path = strtr('zones/:zone_identifier/settings/cache_level', [':zone_identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $request_path, ['value' => $cache_level]);
  }

  /**
   * @param $zone_id
   *
   * @param $ttl
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function setBrowserCacheTtl($zone_id, $ttl) {
    $request_path = strtr('zones/:zone_identifier/settings/cache_level', [':zone_identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $request_path, ['value' => $ttl]);
  }

  /**
   * Sets JS/CSS/HTML minification settings for CloudFlare.
   *
   * CloudFlare will minify resources on the edge.
   *
   * @param $zone_id
   *
   * @param $css
   * @param $html
   * @param $js
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function setMinify($zone_id, $css, $html, $js) {
    $request_path = strtr('zones/:zone_identifier/settings/minify', [':zone_identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $request_path, ['value' => ['css' => $css, 'html' => $html, 'js' => $js]]);
  }

  /**
   * Strips metadata and compresses your images for faster page load times.
   *
   * Off -
   * Basic - (Lossless): Reduce the size of PNG, JPEG, and GIF files..
   * Basic + JPEG (Lossy): Further reduce the size of JPEG files for faster image loading.
   *
   * Larger JPEGs are converted to progressive images, loading a
   * lower-resolution image first and ending in a higher-resolution version.
   * Not recommended for hi-res photography sites.
   *
   * @param $zone_id
   *   The id for the zone to set.
   * @param $polish_level
   *   The polish level to set: off, lossless, lossy.
   *
   * @throws \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiException
   */
  public function setPolish($zone_id, $polish_level) {
    $request_path = strtr('zones/:zone_identifier/settings/polish', [':zone_identifier' => $zone_id]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $request_path, ['value' => $polish_level]);
  }

}
