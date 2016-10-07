<?php

namespace Drupal\cloudflare\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CloudFlarePhpSdk\Exceptions\CloudFlareException;
use CloudFlarePhpSdk\Exceptions\CloudFlareTimeoutException;
use CloudFlarePhpSdk\Exceptions\CloudFlareInvalidCredentialException;
use \InvalidArgumentException;

/**
 * Class SettingsForm.
 *
 * @package Drupal\cloudflare\Form
 */
class SettingsForm extends FormBase implements ContainerInjectionInterface {

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
   * Checks that the composer dependencies for CloudFlare are met.
   *
   * @var \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface
   */
  protected $cloudFlareComposerDependenciesCheck;

  /**
   * Tracks if a zoneId has been set.
   *
   * @var bool
   */
  protected $hasZoneId;

  /**
   * Tracks if valid credentials have been entered.
   *
   * @var bool
   */
  protected $hasValidCredentials;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // This is a hack because could not get custom ServiceProvider to work.
    // See: https://www.drupal.org/node/2026959
    $has_zone_mock = $container->has('cloudflare.zonemock');
    $has_composer_mock = $container->has('cloudflare.composer_dependency_checkmock');

    return new static(
      $container->get('config.factory'),
      $container->get('cloudflare.state'),

      $has_zone_mock ? $container->get('cloudflare.zonemock') : $container->get('cloudflare.zone'),
      $container->get('logger.factory')->get('cloudflare'),
      new EmailValidator(),
      $has_composer_mock ? $container->get('cloudflare.composer_dependency_checkmock') : $container->get('cloudflare.composer_dependency_check')
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
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks if composer dependencies are met.
   */
  public function __construct(ConfigFactoryInterface $config, CloudFlareStateInterface $state, CloudFlareZoneInterface $zone_api, LoggerInterface $logger, EmailValidator $email_validator, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $this->configFactory = $config;
    $this->config = $config->getEditable('cloudflare.settings');
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
    $this->emailValidator = $email_validator;
    $this->cloudFlareComposerDependenciesMet = $check_interface->check();
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
    $this->hasZoneId = !empty($this->config->get('zone_id'));
    $this->hasValidCredentials = $this->config->get('valid_credentials') === TRUE;

    if ($this->hasValidCredentials && $this->cloudFlareComposerDependenciesMet) {
      try {
        $this->zones = $this->zoneApi->listZones();
        $this->hasMultipleZones = count($this->zones) > 1;
      }
      catch (CloudFlareTimeoutException $e) {
        drupal_set_message("Unable to connect to CloudFlare in order to validate credentials.  Connection timed out.  Please try again later.", 'error');
      }
    }
    else {
      $this->zones = [];
      $this->hasMultipleZones = FALSE;
    }

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
    $form = array_merge($form, $this->buildApiCredentialsSection());
    $form = array_merge($form, $this->buildZoneSelectSection());
    $form = array_merge($form, $this->buildGeneralConfig());

    // Form elements are being disabled after parent::buildForm because:
    // 1: parent::buildForm creates the submit button
    // 2: we want to disable the submit button since dependencies unmet.
    if (!$this->cloudFlareComposerDependenciesMet) {
      drupal_set_message((CloudFlareComposerDependenciesCheckInterface::ERROR_MESSAGE), 'error');

      $form['api_credentials_fieldset']['apikey']['#disabled'] = TRUE;
      $form['api_credentials_fieldset']['email']['#disabled'] = TRUE;
      $form['cloudflare_config']['client_ip_restore_enabled']['#disabled'] = TRUE;
      $form['cloudflare_config']['bypass_host']['#disabled'] = TRUE;
      $form['cloudflare_config']['bypass_host']['#disabled'] = TRUE;
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Builds credentials section for inclusion in the settings form.
   *
   * @return array
   *   Form Api render array with credentials section.
   */
  protected function buildApiCredentialsSection() {
    $section = [];

    $section['api_credentials_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
    ];
    $section['api_credentials_fieldset']['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CloudFlare API Key'),
      '#description' => $this->t('Your API key. Get it at <a href="https://www.cloudflare.com/a/account/my-account">cloudflare.com/a/account/my-account</a>.'),
      '#default_value' => $this->config->get('apikey'),
      '#required' => TRUE,
    ];
    $section['api_credentials_fieldset']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account e-mail address'),
      '#default_value' => $this->config->get('email'),
      '#required' => TRUE,
    ];

    return $section;
  }

  /**
   * Builds zone selection section for inclusion in the settings form.
   *
   * @return array
   *   Form Api render array with selection section.
   */
  protected function buildZoneSelectSection() {
    $section = [];

    $section['zone_selection_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Zone Selection'),
      '#weight' => 0,
    ];

    if ($this->hasZoneId) {
      $zone_id = $this->config->get('zone_id');
      $description = $this->t('To change the current zone click the "Next" button below.');
      foreach ($this->zones as $zone) {
        if ($zone->getZoneId() == $zone_id) {
          $zone_text = $zone->getName();
        }
      }
    }

    else {
      $zone_text = "No Zone Selected";
      $description = $this->t('No zone has been selected.  Enter valid Api credentials then click next.');
    }

    $section['zone_selection_fieldset']['zone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current Zone'),
      '#description' => $description,
      '#default_value' => $zone_text,
      '#disabled' => TRUE,
    ];

    return $section;
  }

  /**
   * Builds general config section for inclusion in the settings form.
   *
   * @return array
   *   Form Api render array with selection section.
   */
  protected function buildGeneralConfig() {
    $section = [];

    $section['cloudflare_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];

    $section['cloudflare_config']['client_ip_restore_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restore Client Ip Address'),
      '#description' => $this->t('CloudFlare operates as a reverse proxy and replaces the client IP address. This setting will restore it.<br /> Read more <a href="https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-CloudFlare-handle-HTTP-Request-headers-">here</a>.'),
      '#default_value' => $this->config->get('client_ip_restore_enabled'),
    ];

    $section['cloudflare_config']['bypass_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host to Bypass CloudFlare'),
      '#description' => $this->t('Optional: You can specify a host(no http/https) used for authenticated users to edit the site that bypasses CloudFlare. <br /> This will help suppress log warnings regarding requests bypassing CloudFlare.'),
      '#default_value' => $this->config->get('bypass_host'),
    ];

    return $section;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = trim($form_state->getValue('email'));
    $apikey = trim($form_state->getValue('apikey'));
    $bypass_host = trim($form_state->getValue('bypass_host'));
    $is_email_valid = $this->emailValidator->isValid($email);

    if (!$is_email_valid) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid e-mail address.'));
      return;
    }

    try {
      // Simply using this call to confirm that the credentials can authenticate
      // against the CloudFlareApi. An exception here tell us the credentials
      // are invalid.
      $this->zoneApi->assertValidCredentials($apikey, $email, $this->cloudFlareComposerDependenciesCheck, $this->state);
    }
    catch (CloudFlareTimeoutException $e) {
      $message = $this->t('Unable to connect to CloudFlare in order to validate credentials.  Connection timed out.  Please try again later.');
      $form_state->setErrorByName('apikey', $message);
      $this->logger->error($message);
      return;
    }
    catch (CloudFlareInvalidCredentialException $e) {
      $form_state->setErrorByName('apiKey', $e->getMessage());
      return;
    }
    catch (CloudFlareException $e) {
      $form_state->setErrorByName('apikey', $this->t("An unknown error has occurred when attempting to connect to CloudFlare's API") . $e->getMessage());
      return;
    }

    try {
      $has_http_or_https = strpos($bypass_host, 'http') > -1;
      if ($has_http_or_https) {
        $form_state->setErrorByName('$bypass_host', $this->t('Please enter a host without http/https'));
      }

      // Quick and easy way to validate the domain.
      if (!empty($bypass_host)) {
        $bypass_uri = 'http://' . $bypass_host;
        Url::fromUri($bypass_uri);
      }
    }
    catch (InvalidArgumentException $e) {
      $form_state->setErrorByName('bypass_host', $this->t('You have entered an invalid host.'));
      return;
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_key = trim($form_state->getValue('apikey'));
    $email = trim($form_state->getValue('email'));

    // Deslash the host url.
    $bypass_host = trim(rtrim($form_state->getValue('bypass_host'), "/"));
    $client_ip_restore_enabled = $form_state->getValue('client_ip_restore_enabled');

    $this->config->set('apikey', $api_key)
      ->set('email', $email)
      ->set('valid_credentials', TRUE)
      ->set('bypass_host', $bypass_host)
      ->set('client_ip_restore_enabled', $client_ip_restore_enabled);

    $this->config->save();
  }

}
