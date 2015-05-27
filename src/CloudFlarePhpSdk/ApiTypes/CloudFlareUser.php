<?php

/**
 * @file
 * Implementation for CloudFlareUser class.
 */

namespace Drupal\cloudflare\CloudFlarePhpSdk\ApiTypes;
/**
 * This class provides typed storage for User information from CloudFlare.
 */
class CloudFlareUser {
  private $id;
  private $email;
  private $username;
  private $firstName;
  private $lastName;
  private $telephone;
  private $country;
  private $zipcode;
  private $twoFactorAuthentication;
  private $createdOn;
  private $modifiedOn;
  private $hasProZones;
  private $hasBusinessZones;
  private $hasEnterpriseZones;


  public function getOrganizations() {
    return $this->organizations;
  }

  /**
   * Gets the id of the CloudFlare user.
   *
   * @return string|null
   *   Id of the CloudFlare user.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the email address of the CloudFlare user.
   *
   * @return mixed
   *   Email address of the CloudFlare user
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Gets the username of the CloudFlare user.
   *
   * @return string|null
   *   Username of the CloudFlare user.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Gets the first name of the CloudFlare user.
   *
   * @return string|null
   *   The first name of the CloudFlare user.
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * Gets the last name of the CloudFlare user.
   *
   * @return string|null
   *   The last name of the CloudFlare user.
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * Gets the telephone number of the CloudFlare user.
   *
   * @return string|null
   *   The telephone number of the CloudFlare user.
   */
  public function getTelephone() {
    return $this->telephone;
  }

  /**
   * Gets the country of the CloudFlare user.
   *
   * @return string|null
   *   The country of the CloudFlare user.
   */
  public function getCountry() {
    return $this->country;
  }

  /**
   * Gets the zipcode of the CloudFlare user.
   *
   * @return string|null
   *   The zipcode of the CloudFlare user.
   */
  public function getZipcode() {
    return $this->zipcode;
  }

  /**
   * Checks if two factor authentication enabled.
   *
   * @return bool
   *   TRUE if two factor authentication enabled. FALSE otherwise.
   */
  public function isTwoFactorAuthenticationEnabled() {
    return $this->twoFactorAuthentication;
  }

  /**
   * Gets the timestamp when the CloudFlare user was created.
   *
   * @return datetime|null
   *   The timestamp when the CloudFlare user was created.
   */
  public function getCreatedOn() {
    return $this->createdOn;
  }

  /**
   * Gets the timestamp when the CloudFlare user was last modified.
   *
   * @return datetime|null
   *   The timestamp when the CloudFlare user was last modified.
   */
  public function getModifiedOn() {
    return $this->modifiedOn;
  }

  /**
   * Checks if pro zones are enabled.
   *
   * @return bool|null
   *   TRUE if pro zones enabled.  FALSE otherwise.
   */
  public function hasProZones() {
    return $this->hasProZones;
  }

  /**
   * Checks if business zones are enabled.
   *
   * @return bool|null
   *   TRUE if business zones enabled.  FALSE otherwise.
   */
  public function getHasBusinessZones() {
    return $this->hasBusinessZones;
  }

  /**
   * Checks if enterprise zones are enabled.
   *
   * @return bool|null
   *   TRUE if enterprise zones enabled.  FALSE otherwise.
   */
  public function getHasEnterpriseZones() {
    return $this->hasEnterpriseZones;
  }

  /**
   * Default constructor for CloudFlareUser class.
   */
  public function __construct(array $params) {
    $this->id = $params['id'];
    $this->email = $params['email'];
    $this->username = $params['username'];
    $this->firstName = $params['firstName'];
    $this->lastName = $params['lastName'];
    $this->telephone = $params['telephone'];
    $this->country = $params['country'];
    $this->zipcode = $params['zipcode'];
    $this->twoFactorAuthentication = (boolean) $params['two_factor_authentication_enabled'];
    $this->createdOn = $params['created_on'];
    $this->modifiedOn = $params['modifiedOn'];
    $this->organizations = $params['organizations'];
    $this->hasProZones = $params['hasProZones'];
    $this->hasBusinessZones = $params['hasBusinessZones'];
    $this->hasEnterpriseZones = $params['hasEnterpriseZones'];
  }

}
