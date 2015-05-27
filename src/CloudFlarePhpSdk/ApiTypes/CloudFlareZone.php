<?php

/**
 * @file
 * Implementation for CloudFlareZone class.
 */

namespace Drupal\cloudflare\CloudFlarePhpSdk\ApiTypes;
/**
 * This class provides typed storage for Zone information from CloudFlare.
 */
class CloudFlareZone {
  private $id;
  private $name;
  private $status;
  private $paused;
  private $type;
  private $developmentMode;
  private $nameServers;
  private $originalNameServers;
  private $originalRegistrar;
  private $originalDnshost;
  private $modifiedOn;
  private $createdOn;
  private $meta;
  private $owner;
  private $permissions;
  private $plan;


  /**
   * Default constructor for CloudFlareZone object.
   *
   * @param array $cloudflare_zone_settings
   *   Associative array of CloudFlare settings.
   */
  public function __construct(array $cloudflare_zone_settings) {
    $this->id = $cloudflare_zone_settings['id'];
    $this->name = $cloudflare_zone_settings['name'];
    $this->status = $cloudflare_zone_settings['status'];
    $this->paused = $cloudflare_zone_settings['paused'];
    $this->type = $cloudflare_zone_settings['type'];
    $this->developmentMode = $cloudflare_zone_settings['development_mode'];
    $this->name_servers = $cloudflare_zone_settings['name_servers'];
    $this->original_name_servers = $cloudflare_zone_settings['original_name_servers'];
    $this->original_registrar = $cloudflare_zone_settings['original_registrar'];
    $this->original_dnshost = $cloudflare_zone_settings['original_dnshost'];
    $this->modified_on = $cloudflare_zone_settings['modifiedOn'];
    $this->created_on = $cloudflare_zone_settings['created_on'];
    $this->meta = $cloudflare_zone_settings['meta'];
    $this->owner = $cloudflare_zone_settings['owner'];
    $this->permissions = $cloudflare_zone_settings['permissions'];
    $this->plan = $cloudflare_zone_settings['plan'];
  }

  /**
   * Gets the current zone's ID.
   *
   * @return string|null
   *   The current zone's ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the name of the current zone.
   *
   * @return string|null
   *   The name of the current zone
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get's the zones status.
   *
   * @return string
   *   Result can be active, pending, initializing, moved, deleted, or
   *   deactivated.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Checks if the zone is paused.
   *
   * A true value means the zone will not receive security or performance
   * benefits.
   *
   * @return bool
   *   If TRUE cloudflare security and performance enhancements are disabled.
   *   FALSE otherwise.
   */
  public function isZonePaused() {
    return $this->paused;
  }

  /**
   * Gets the zone's type.
   *
   * A full zone implies that DNS is hosted with CloudFlare. A partial zone is
   * typically a partner-hosted zone or a CNAME setup.
   *
   * @return bool
   *   If TRUE CloudFlare security and performance enhancements are disabled.
   *   FALSE otherwise.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Checks if development mode is enabled for the zone.
   *
   * Development Mode will bypass CloudFlare's accelerated cache for zone.
   * Once entered, development mode will last for 3 hours and then automatically
   * toggle off.
   *
   * @return bool
   *   TRUE if Development mode is enabled for the zone.  FALSE otherwise.
   */
  public function isDevelopmentModeEnabled() {
    return $this->developmentMode;
  }

  /**
   * Gets the current nameservers for the zone.
   *
   * @return array|null
   *   The original DNS servers.
   */
  public function getNameServers() {
    return $this->nameServers;
  }

  /**
   * Gets nameservers for the zone before DNS was transferred to CloudFlare.
   *
   * @return array|null
   *   The original DNS servers.
   */
  public function getOriginalNameServers() {
    return $this->originalNameServers;
  }

  /**
   * The registrar for the domain before DNS was transferred to CloudFlare.
   *
   * @return array|null
   *   The original host record.
   */
  public function getOriginalRegistrar() {
    return $this->originalRegistrar;
  }

  /**
   * Gets original host for the zone before DNS was transferred to CloudFlare.
   *
   * @return array|null
   *   The original host record.
   */
  public function getOriginalDnshost() {
    return $this->originalDnshost;
  }

  /**
   * Gets the timestamp when the CloudFlare user was modified.
   *
   * @return datetime|null
   *   The timestamp when the CloudFlare user was modified.
   */
  public function getModifiedOn() {
    return $this->modifiedOn;
  }

  /**
   * Gets the timestamp when the CloudFlare zone was created.
   *
   * @return datetime|null
   *   The timestamp when the CloudFlare zone was created.
   */
  public function getCreatedOn() {
    return $this->createdOn;
  }

  /**
   * Metadata about the domain.
   *
   * @return array
   *   Domain metadata.
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * Gets Information about the owner of the zone.
   *
   * The zone owner can be either an individual or an organization.
   *
   * @return array
   *   Information about the owner of the zone.
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * Available permissions on the zone for the current user requesting the item.
   *
   * @return array
   *   Zone permissions for the current user.
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * Plan information for the current zone.
   *
   * @return array
   *   The zone's information.
   */
  public function getPlan() {
    return $this->plan;
  }

}
