<?php

namespace Drupal\cloudflare;

use Drupal\Core\Config\Config;

/**
 * Handles configuration of credentials.
 *
 * @package Drupal\cloudflare
 */
class CloudFlareCredentials {

  /**
   * The email address (user account).
   *
   * @var string
   */
  protected $email = '';

  /**
   * The apikey.
   *
   * @var string
   */
  protected $apikey = '';

  /**
   * CloudFlareCredentials constructor.
   *
   * @param \Drupal\Core\Config\Config|null $config
   *   The cloudflare configuration object.
   */
  public function __construct(Config $config = NULL) {
    if ($config) {
      $credential_provider = $config->get('credential_provider');
      $credentials = $config->get('credentials');
      if ($credentials) {
        $this->setCredentials($credential_provider, $credentials);
      }
    }
  }

  /**
   * Set the credentials from configuration array.
   *
   * @param string $credential_provider
   *   The credential provider.
   * @param array $providers
   *   Nested array of all the credential providers.
   */
  public function setCredentials($credential_provider, array $providers) {
    switch ($credential_provider) {
      case 'cloudflare':
        $this->email = $providers['cloudflare']['email'];
        $this->apikey = $providers['cloudflare']['apikey'];
        break;

      case 'key':
        $this->email = $providers['key']['email'];

        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('key');
        /** @var \Drupal\key\KeyInterface $apikey_key */
        $apikey_key = $storage->load($providers['key']['apikey_key']);
        if ($apikey_key) {
          $this->apikey = $apikey_key->getKeyValue();
        }
        break;

      case 'multikey':
        /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('key');
        /** @var \Drupal\key\KeyInterface $key */
        $key = $storage->load($providers['multikey']['email_apikey_key']);
        if ($key) {
          $values = $key->getKeyValues();
          $this->email = $values['username'];
          $this->apikey = $values['password'];
        }
        break;
    }
  }

  /**
   * Return the email address.
   *
   * @return string
   *   The email.
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Return the API Key.
   *
   * @return string
   *   The API key.
   */
  public function getApikey() {
    return $this->apikey;
  }

}
