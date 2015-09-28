<?php
/**
 * @file
 * Contains \Drupal\cloudflare\CloudflareConfigInterface.
 */

namespace Drupal\cloudflare;

/**
 * Zone methods for CloudFlare.
 */
interface CloudFlareConfigInterface {
  /**
   * Determine the current Cloudflare ZoneId for the site.
   *
   * Most CloudFlare accounts have a single zone. It can be assumed as the
   * default. If there is more than one the user needs to specify the zone.
   * on the cloudflare config page.
   *
   * @return string
   *   The id of the current zone.
   */
  public function getCurrentZoneId();

  /**
   * Gets the currently instantiated ZoneApi object for the site.
   *
   * @return \CloudFlarePhpSdk\ApiEndpoints\ZoneApi
   *   Instance of ZoneApi for the site.
   */
  public function getZoneApi();

  /**
   * Gets the current logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   The current logger.
   */
  public function getLogger();

  /**
   * Checks if the site has valid CloudFlare Api credentials.
   *
   * @return bool|NULL
   *   TRUE if there are valid credentials.  FALSE otherwise.
   */
  public function hasValidApiCredentials();

}
