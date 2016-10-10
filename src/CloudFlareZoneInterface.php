<?php

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
   * @return \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings|null
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
   *   Application level error returned from the API.
   */
  public function listZones();

  /**
   * Asserts that credentials are valid. Does NOT pull settings from CMI.
   *
   * @param string $apikey
   *   The secret Api key used to authenticate against CloudFlare.
   * @param string $email
   *   Email of the account used to authenticate against CloudFlare.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $composer_dependency_check
   *   Checks that composer dependencies are met.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   *
   * @throws CloudFlarePhpSdk\Exceptions\CloudFlareInvalidCredentialException
   *   Thrown if $apikey and $email fail to authenticate against the Api.
   * @throws CloudFlarePhpSdk\Exceptions\CloudFlareTimeoutException
   *   Thrown if the connection to the Api times out.
   * @throws CloudFlarePhpSdk\Exceptions\CloudFlareException
   *   Thrown if an unknown exception occurs when connecting to the Api.
   */
  public static function assertValidCredentials($apikey, $email, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state);

}
