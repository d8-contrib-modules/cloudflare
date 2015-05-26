<?php
namespace Drupal\cloudflare\CloudFlarePhpSdk\ApiTypes;


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
  private $created_on;
  private $modified_on;
  private $has_pro_zones;
  private $has_business_zones;
  private $has_enterprise_zones;

  public function __construct(array $params) {
    $this->id = $params['id'];
    $this->email = $params['email'];
    $this->username = $params['username'];
    $this->firstName = $params['firstName'];
    $this->lastName = $params['lastName'];
    $this->telephone = $params['telephone'];
    $this->country = $params['country'];
    $this->zipcode = $params['zipcode'];
    $this->twoFactorAuthentication = $params['two_factor_authentication_enabled'];
    $this->created_on = $params['created_on'];
    $this->modified_on = $params['modified_on'];
    $this->organizations = $params['organizations'];
    $this->has_pro_zones = $params['has_pro_zones'];
    $this->has_business_zones = $params['has_business_zones'];
    $this->has_enterprise_zones = $params['has_enterprise_zones'];
  }
}