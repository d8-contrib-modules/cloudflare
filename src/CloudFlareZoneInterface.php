<?php
/**
 * @file
 * Contains \Drupal\cloudflare\CloudflareZoneInterface.
 */

namespace Drupal\cloudflare;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;

/**
 * Zone methods for CloudFlare.
 */
interface CloudFlareZoneInterface {

  /**
   * Updates a zone's settings from CloudFlare's API.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings $zone_settings
   *   The updated settings object.
   */
  public function updateZoneSettings(ZoneSettings $zone_settings);

  /**
   * Gets the zone's settings from CloudFlare's API.
   *
   * @return \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings|NULL
   *   Zone settings retrieved from the CloudFlareAPI. NULL if unable to
   *   retrieve.
   */
  public function getZoneSettings();

  /**
   * Retrieves a listing of zones in the current CloudFlare account.
   *
   * @return array
   *   A array of CloudFlareZones objects from the current CloudFlare account.
   *
   * @throws \CloudFlarePhpSdk\Exceptions\CloudFlareApiException
   *   Throws an exception if there is an application level error returned from
   *   the API.
   */
  public function listZones();

}
