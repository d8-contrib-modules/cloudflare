<?php

/**
 * @file
 * Contains Drupal\cloudflaremanualinvalidator\Form\CloudFlareCacheClearForm.
 */

namespace Drupal\cloudflaremanualinvalidator\Form;

use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class DefaultForm.
 *
 * @package Drupal\cloudflare\Form
 */
class CloudFlareCacheClearForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cloudflare.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudflare_cache_clear';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['path_clearing'] = [
      '#type' => 'fieldset',
      '#title' => t('Path Clear Cache'),
    ];

    $form['path_clearing']['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path\'s to clear'),
      '#description' => $this->t('You can enter multiple paths. One per line. Note CloudFlare\'s API only allows a max of ' . ZoneApi::MAX_PURGES_PER_REQUEST . ' path purges per request. It also does not handle wildcards at this time.'),
    ];

    $form['path_clearing']['paths_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Specified Paths'),
      '#validate' => [[$this, 'validatePathClear']],
      '#submit' => ['::purgePaths'],
    ];

    $form['cache_clearing'] = [
      '#type' => 'fieldset',
      '#title' => t('Global Cache Clear'),
    ];

    $form['cache_clearing']['clear_zone_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Entire Cachee'),
      '#submit' => ['::purgeEntireZone'],
    ];
    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validatePathClear(array &$form, FormStateInterface $form_state) {
    $raw_paths = $form_state->getValue('paths');
    $paths = explode('\n', $raw_paths);
    $exceeds_allowed_max_purges = count($paths) > ZoneApi::MAX_PURGES_PER_REQUEST;

    if ($exceeds_allowed_max_purges) {
      $form_state->setErrorByName('paths', $this->t('Unfortunately you entered more than the maximum number of requests. The CloudFlare api limits you to ' . ZoneApi::MAX_PURGES_PER_REQUEST . 'paths per request.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Callback function to purge an entire zone.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function purgeEntireZone(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('cloudflare.settings');
    $api_key = $config->get('apikey');
    $email = $config->get('email');
    $zone = $config->get('zone');

    try {
      $this->zoneApi = new ZoneApi($api_key, $email);

      // @todo rethink how to handle cloudflare zones in Drupal.
      if (is_null($zone)) {
        $zones = $this->zoneApi->listZones();
        $zone = $zones[0]->getZoneId();
      }

      $this->zoneApi->purgeAllFiles($zone);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to clear zone cache. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to clear zone cache. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    // If no exceptions have been thrown then the request has been successful.
    drupal_set_message("The zone: $zone was successfully cleared.");
  }

  /**
   * Form callback to purge paths.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function purgePaths(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cloudflare.settings');
    $api_key = $config->get('apikey');
    $email = $config->get('email');
    $zone = $config->get('zone');

    try {
      $zone_api = new ZoneApi($api_key, $email);

      // @todo rethink how to handle cloudflare zones in Drupal.
      if (is_null($zone)) {
        $zones = $zone_api->listZones();
        $zone = $zones[0]->getZoneId();
      }
      $zone_api->purgeAllFiles($zone);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to clear paths. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to clear paths. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    // If no exceptions have been thrown then the request has been successful.
    drupal_set_message("The zone: $zone was successfully cleared.");
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo figure out how to replace formbase so that this is not necessary.
  }

}
