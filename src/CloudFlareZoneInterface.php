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
interface CloudflareZoneInterface {

  /**
   * Updates the current zone's settings from CloudFlare's API.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings $zone_settings
   *   The updated settings object.
   */
  public function updateZone(ZoneSettings $zone_settings);

  /**
   * Gets the current zone's settings from CloudFlare's API.
   *
   * @return \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings|NULL
   *   Zone settings retrieved from the CloudFlareAPI. Void if unable to
   *   retrieve.
   */
  public function getZoneSettings();

}
