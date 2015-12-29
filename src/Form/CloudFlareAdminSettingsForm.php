<?php

/**
 * @file
 * Contains Drupal\cloudflare\Form\CloudFlareAdminSettingsForm.
 */

namespace Drupal\cloudflare\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\Exceptions\CloudFlareException;
use \InvalidArgumentException;

/**
 * Class CloudFlareAdminSettingsForm.
 *
 * @package Drupal\cloudflare\Form
 */
class CloudFlareAdminSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Email validator class.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Wrapper to access the CloudFlare zone api.
   *
   * @var \Drupal\cloudflare\CloudFlareZoneInterface
   */
  protected $zoneApi;

  /**
   * The cloudflare settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger instance for cloudflare.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cloudflare.state'),
      $container->get('cloudflare.zone'),
      $container->get('logger.factory')->get('cloudflare'),
      new EmailValidator()
    );
  }

  /**
   * Constructs a new CloudFlareAdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(ConfigFactoryInterface $config, CloudFlareStateInterface $state, CloudFlareZoneInterface $zone_api, LoggerInterface $logger, EmailValidator $email_validator) {
    $this->config = $config->getEditable('cloudflare.settings');
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cloudflare.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudflare_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_credentials_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
    ];

    $form['cloudflare_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];

    $form['api_credentials_fieldset']['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CloudFlare API Key'),
      '#description' => $this->t('Your API key. Get it at <a href="https://www.cloudflare.com/a/account/my-account">cloudflare.com/a/account/my-account</a>.'),
      '#default_value' => $this->config->get('apikey'),
      '#required' => TRUE,
    ];
    $form['api_credentials_fieldset']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account e-mail address'),
      '#default_value' => $this->config->get('email'),
      '#required' => TRUE,
    ];
    $form['cloudflare_config']['client_ip_restore_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restore Client Ip Address'),
      '#description' => $this->t('CloudFlare operates as a reverse proxy and replaces the client IP address. This setting will restore it.<br /> Read more <a href="https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-CloudFlare-handle-HTTP-Request-headers-">here</a>.'),
      '#default_value' => $this->config->get('client_ip_restore_enabled'),
    ];

    $form['cloudflare_config']['bypass_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host to Bypass CloudFlare'),
      '#description' => $this->t('Optional: You can specify a host(no http/https) used for authenticated users to edit the site that bypasses CloudFlare. <br /> This will help suppress watchdog warnings regarding requests bypassing CloudFlare.'),
      '#default_value' => $this->config->get('bypass_host'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = trim($form_state->getValue('email'));
    $apikey = trim($form_state->getValue('apikey'));
    $bypass_host = trim($form_state->getValue('bypass_host'));
    $is_email_valid = $this->emailValidator->isValid($email);

    // Using the bare-metal-class here for diagnostic purposes.
    $zone_api_direct = new ZoneApi($apikey, $email);

    if (!$is_email_valid) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid e-mail address.'));
    }

    try {
      // Simply using this call to confirm that the creds can authenticate
      // against the CloudFlareApi. An exception here tell us the creds are
      // invalid.
      $zone_api_direct->listZones();
      $this->state->incrementApiRateCount();
    }

    catch (\Exception $e) {
      $form_state->setErrorByName('apikey', $this->t('Unfortunately your credentials failed to authenticate against the CloudFlare API.  Please enter valid credentials.'));
    }

    try {
      $has_http = strpos($bypass_host, 'http') > -1;
      if ($has_http) {
        $form_state->setErrorByName('$bypass_host', $this->t('Please enter a host without http/https'));

      }

      // Quick and easy way to validate the Domain.
      if (!empty($bypass_host)) {
        $bypass_uri = 'http://' . $bypass_host;
        Url::fromUri($bypass_uri);
      }
    }

    catch (InvalidArgumentException $e) {
      $form_state->setErrorByName('bypass_host', $this->t('You have entered an invalid host.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Using the bare-metal-class here since the credentials have not yet been
    // added to the system.
    $this->zoneApi = new ZoneApi($form_state->getValue('apikey'), $form_state->getValue('email'));
    $zone_id = $this->getZoneId();
    $api_key = trim($form_state->getValue('apikey'));
    $email = trim($form_state->getValue('email'));
    $bypass_host = trim(rtrim($form_state->getValue('bypass_host'), "/"));
    $client_ip_restore_enabled = $form_state->getValue('client_ip_restore_enabled');

    $this->config->set('apikey', $api_key)
      ->set('email', $email)
      ->set('valid_credentials', TRUE)
      ->set('bypass_host', $bypass_host)
      ->set('client_ip_restore_enabled', $client_ip_restore_enabled);

    if ($zone_id) {
      $this->config->set('zone', $zone_id);
    }

    $this->config->save();
  }

  /**
   * Attempts to determine the current cloudflare zone id.
   *
   * @return string|NULL
   *   A CloudFlare zone Id.
   */
  public function getZoneId() {
    // If there is no zone set and the account only has a single zone.
    try {
      $zones_from_api = $this->zoneApi->listZones();
    }
    catch (CloudFlareException $e) {
      $this->logger->error($e->getMessage());
      return NULL;
    }

    $num_zones_from_api = count($zones_from_api);
    $is_single_zone_cloudflare_account = $num_zones_from_api == 1;
    if ($is_single_zone_cloudflare_account) {
      // If there is a default zone return it so we can set it in CMI.
      $zone_id = $zones_from_api[0]->getZoneId();
      return $zone_id;
    }

    // If the zone has multiple accounts and none is specified in CMI we cannot
    // move forward.
    if (!$is_single_zone_cloudflare_account) {
      $link_to_settings = Url::fromRoute('cloudflare.admin_settings_form')->toString();
      $message = $this->t('No default zone has been entered for CloudFlare. Please go <a href="@link_to_settings">here</a> to set.', ['@link_to_settings' => $link_to_settings]);
      $this->logger->error($message);
      return NULL;
    }
  }

}
