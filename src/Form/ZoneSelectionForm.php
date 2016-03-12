<?php

/**
 * @file
 * Contains Drupal\cloudflare\Form\ZoneSelectionForm.
 */

namespace Drupal\cloudflare\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CloudFlarePhpSdk\Exceptions\CloudFlareTimeoutException;

/**
 * Class ZoneSelectionForm.
 *
 * @package Drupal\cloudflare\Form
 */
class ZoneSelectionForm extends FormBase implements ContainerInjectionInterface {
  /**
   * List of the zones for the current Api credentials.
   *
   * @var array
   */
  protected $zones;

  /**
   * Tracks if the current CloudFlare account has multiple zones.
   *
   * @var bool
   */
  protected $hasMultipleZones;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // This is a hack because could not get custom ServiceProvider to work.
    // this to work: https://www.drupal.org/node/2026959
    $has_zone_mock = $container->has('cloudflare.zonemock');

    return new static(
      $container->get('config.factory'),
      $has_zone_mock ? $container->get('cloudflare.zonemock') : $container->get('cloudflare.zone'),
      $container->get('logger.factory')->get('cloudflare'),
      $container->get('cloudflare.composer_dependency_check')->check()
    );
  }

  /**
   * Constructs a new ZoneSelectionForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param bool $composer_dependencies_met
   *   Checks that the composer dependencies for CloudFlare are met.
   */
  public function __construct(ConfigFactoryInterface $config, CloudFlareZoneInterface $zone_api, LoggerInterface $logger, $composer_dependencies_met) {
    $this->configFactory = $config;
    $this->config = $config->getEditable('cloudflare.settings');
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
    $this->cloudFlareComposerDependenciesMet = $composer_dependencies_met;
    $this->hasZoneId = !empty($this->config->get('zone_id'));
    $this->hasValidCredentials = $this->config->get('valid_credentials') === TRUE;

    // This test should be unnecessary since this form should only ever be
    // reached when the 2 conditions are met. It's being done from an abundance
    // of caution.
    if ($this->hasValidCredentials && $this->cloudFlareComposerDependenciesMet) {
      try {
        $this->zones = $this->zoneApi->listZones();
        $this->hasMultipleZones = count($this->zones) > 1;
      }
      catch (CloudFlareTimeoutException $e) {
        drupal_set_message("Unable to connect to CloudFlare. You will not be able to change the selected Zone.", 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cloudflare.zoneselection',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudflare_zone_selection';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->buildZoneSelectSection();
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
      '#weight' => 0,
    ];

    if (!$this->hasMultipleZones && $this->hasValidCredentials) {
      $zone_id = $this->zones[0]->getZoneId();
      $this->config->set('zone_id', $zone_id)->save();
      $section['zone_selection_fieldset']['zone_selection'] = [
        '#markup' => $this->t('<p>Your CloudFlare account has a single zone which has been automatically selected for you.  Simply click "Finish" to save your settings.</p>'),
      ];

      return $section;
    }

    $listing = $this->buildZoneListing();
    $section['zone_selection_fieldset']['zone_selection'] = $listing;
    return $section;
  }

  /**
   * Builds a form render array for zone selection.
   *
   * @return array
   *   Form Api Render array for zone select.
   */
  public function buildZoneListing() {
    $form_select_field = [];
    $zone_select = [];

    foreach ($this->zones as $zone) {
      $zone_select[$zone->getZoneId()] = $zone->getName();
    }

    $form_select_field = [
      '#type' => 'select',
      '#title' => $this->t('Zone'),
      '#disabled' => FALSE,
      '#options' => $zone_select,
      '#description' => $this->t('Select the top level domain/zone for the current site.'),
      '#default_value' => $this->config->get('zone_id'),
      '#empty_option' => '- None -',
      '#empty_value' => '0',
    ];

    return $form_select_field;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->hasMultipleZones) {
      $zone_id = $form_state->getValue('zone_selection');
      $this->config->set('zone_id', $zone_id)->save();
    }

    $form_state->setRedirect('cloudflare.admin_settings_form');
  }

}
