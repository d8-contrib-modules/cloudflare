<?php

namespace Drupal\cloudflare\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
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
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A logger instance for CloudFlare.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Tracks rate limits associated with CloudFlare API.
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
   * Boolean indicates if CloudFlare dependencies have been met.
   *
   * @var bool
   */
  protected $cloudFlareComposerDependenciesMet;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare API.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks if composer dependencies are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CloudFlareStateInterface $state, CloudFlareZoneInterface $zone_api, LoggerInterface $logger, EmailValidator $email_validator, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
    $this->emailValidator = $email_validator;
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
    $this->cloudFlareComposerDependenciesMet = $check_interface->check();
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
    $config = $this->configFactory->get('cloudflare.settings');
    $form = array_merge($form, $this->buildApiCredentialsSection($config));
    $form = array_merge($form, $this->buildZoneSelectSection($config));
    $form = array_merge($form, $this->buildGeneralConfig($config));

    // Form elements are being disabled after parent::buildForm because:
    // 1: parent::buildForm creates the submit button
    // 2: we want to disable the submit button since dependencies unmet.
    if (!$this->cloudFlareComposerDependenciesMet) {
      drupal_set_message((CloudFlareComposerDependenciesCheckInterface::ERROR_MESSAGE), 'error');

      $form['api_credentials_fieldset']['apikey']['#disabled'] = TRUE;
      $form['api_credentials_fieldset']['email']['#disabled'] = TRUE;
      $form['cloudflare_config']['client_ip_restore_enabled']['#disabled'] = TRUE;
      $form['cloudflare_config']['bypass_host']['#disabled'] = TRUE;
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Builds credentials section for inclusion in the settings form.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The readonly configuration.
   *
   * @return array
   *   Form Api render array with credentials section.
   */
  protected function buildApiCredentialsSection(Config $config) {
    $section = [];

    $section['api_credentials_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
    ];
    $section['api_credentials_fieldset']['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CloudFlare API Key'),
      '#description' => $this->t('Your API key. Get it at <a href="https://www.cloudflare.com/a/account/my-account">cloudflare.com/a/account/my-account</a>.'),
      '#default_value' => $config->get('apikey'),
      '#required' => TRUE,
    ];
    $section['api_credentials_fieldset']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account e-mail address'),
      '#default_value' => $config->get('email'),
      '#required' => TRUE,
    ];

    return $section;
  }

  /**
   * Builds zone selection section for inclusion in the settings form.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The readonly configuration.
   *
   * @return array
   *   Form Api render array with selection section.
   */
  protected function buildZoneSelectSection(Config $config) {
    $section = [];

    $section['zone_selection_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Zone Selection'),
      '#weight' => 0,
    ];

    $zone_id = $config->get('zone_id');
    if (!empty($zone_id)) {
      // Get the zones.
      $zones = [];
      if ($config->get('valid_credentials') === TRUE && $this->cloudFlareComposerDependenciesMet) {
        try {
          $zones = $this->zoneApi->listZones();
        }
        catch (CloudFlareTimeoutException $e) {
          drupal_set_message($this->t('Unable to connect to CloudFlare in order to validate credentials. Connection timed out. Please try again later.'), 'error');
        }
      }

      // Find this zone_id.
      foreach ($zones as $zone) {
        if ($zone->getZoneId() == $zone_id) {
          $zone_text = $zone->getName();
          break;
        }
      }

      $description = $this->t('To change the current zone click the "Next" button below.');
    }
    else {
      $zone_text = $this->t('No Zone Selected');
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
   * @param \Drupal\Core\Config\Config $config
   *   The readonly configuration.
   *
   * @return array
   *   Form API render array with selection section.
   */
  protected function buildGeneralConfig(Config $config) {
    $section = [];

    $section['cloudflare_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];

    $section['cloudflare_config']['client_ip_restore_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restore Client Ip Address'),
      '#description' => $this->t('CloudFlare operates as a reverse proxy and replaces the client IP address. This setting will restore it.<br /> Read more <a href="https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-CloudFlare-handle-HTTP-Request-headers-">here</a>.'),
      '#default_value' => $config->get('client_ip_restore_enabled'),
    ];

    $section['cloudflare_config']['bypass_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host to Bypass CloudFlare'),
      '#description' => $this->t('Optional: Specify a host (no http/https) used for authenticated users to edit the site that bypasses CloudFlare. <br /> This will help suppress log warnings regarding requests bypassing CloudFlare.'),
      '#default_value' => $config->get('bypass_host'),
    ];

    return $section;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get the email address and apikey.
    $email = trim($form_state->getValue('email'));
    $apikey = trim($form_state->getValue('apikey'));

    // Validate the email address.
    if (!$this->emailValidator->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid e-mail address.'));
      return;
    }

    try {
      // Confirm that the credentials can authenticate with the CloudFlareApi.
      $this->zoneApi->assertValidCredentials($apikey, $email, $this->cloudFlareComposerDependenciesCheck, $this->state);
    }
    catch (CloudFlareTimeoutException $e) {
      $message = $this->t('Unable to connect to CloudFlare in order to validate credentials. Connection timed out. Please try again later.');
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

    // Validate the bypass host.
    $bypass_host = trim($form_state->getValue('bypass_host'));
    if (!empty($bypass_host)) {
      // Validate the bypass host does not begin with http.
      if (strpos($bypass_host, 'http') > -1) {
        $form_state->setErrorByName('$bypass_host', $this->t('Please enter a host without http/https'));
        return;
      }

      // Validate the host domain.
      try {
        Url::fromUri("http://$bypass_host");
      }
      catch (\InvalidArgumentException $e) {
        $form_state->setErrorByName('bypass_host', $this->t('You have entered an invalid host.'));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_key = trim($form_state->getValue('apikey'));
    $email = trim($form_state->getValue('email'));

    // Deslash the host URL.
    $bypass_host = trim(rtrim($form_state->getValue('bypass_host'), "/"));
    $client_ip_restore_enabled = $form_state->getValue('client_ip_restore_enabled');

    $config = $this->configFactory->getEditable('cloudflare.settings');
    $config
      ->set('apikey', $api_key)
      ->set('email', $email)
      ->set('valid_credentials', TRUE)
      ->set('bypass_host', $bypass_host)
      ->set('client_ip_restore_enabled', $client_ip_restore_enabled);
    $config->save();
  }

}
