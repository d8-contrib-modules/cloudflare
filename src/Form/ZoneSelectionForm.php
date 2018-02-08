<?php

namespace Drupal\cloudflare\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use CloudFlarePhpSdk\Exceptions\CloudFlareTimeoutException;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ZoneSelectionForm.
 *
 * @package Drupal\cloudflare\Form
 */
class ZoneSelectionForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Wrapper to access the CloudFlare zone api.
   *
   * @var \Drupal\cloudflare\CloudFlareZoneInterface
   */
  protected $zoneApi;

  /**
   * A logger instance for CloudFlare.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * List of the zones for the current Api credentials.
   *
   * @var array
   */
  protected $zones;

  /**
   * Boolean indicates if CloudFlare dependencies have been met.
   *
   * @var bool
   */
  protected $cloudFlareComposerDependenciesMet;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param bool $composer_dependencies_met
   *   Checks that the composer dependencies for CloudFlare are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CloudFlareZoneInterface $zone_api, LoggerInterface $logger, $composer_dependencies_met) {
    $this->configFactory = $config_factory;
    $this->config = $config_factory->getEditable('cloudflare.settings');
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
        drupal_set_message($this->t('Unable to connect to CloudFlare. You will not be able to change the selected Zone.'), 'error');
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

      // It is possible to authenticate with the API without having configured a
      // domain in the CloudFlare console. This prevents a fatal error where
      // zones[0]->getZoneId() is called on a NULL reference.
      if (empty($this->zones)) {
        $add_site_link = Link::fromTextAndUrl(
          $this->t('add a site'),
          Url::fromUri('https://www.cloudflare.com/a/setup')
        );
        $section['zone_selection_fieldset']['zone_selection'] = [
          '#markup' => $this->t('<p>Your CloudFlare account does not have any zones configured. Verify your API details or !add_site_link via the console.</p>', [
            '!add_site_link' => $add_site_link->toString(),
          ]),
        ];
        return $section;
      }

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
      '#type' => 'textfield',
      '#title' => $this->t('Zone'),
      '#disabled' => FALSE,
      '#options' => $zone_select,
      '#description' => $this->t('Use the autocomplete to select your zone (top level domain for the site). The zone ID corresponding to the domain will then be saved in the field.'),
      '#default_value' => $this->config->get('zone_id'),
      '#empty_option' => '- None -',
      '#empty_value' => '0',
      '#autocomplete_route_name' => 'cloudflare.zone_autocomplete',
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

  /**
   * Retrieves suggestions for zone autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocompleteZone(Request $request) {
    $zone_autocomplete_text = $request->query->get('q');
    $matches = [];

    // Tracks if the current CloudFlare account has multiple zones.
    /** @var \CloudFlarePhpSdk\ApiTypes\Zone\Zone $zone */
    foreach ($this->zoneApi->listZones() as $zone) {
      if (stripos($zone->getName(), $zone_autocomplete_text) === 0) {
        $matches[] = ['value' => $zone->getZoneId(), 'label' => $zone->getName()];
      }
    }
    return new JsonResponse($matches);
  }

}
