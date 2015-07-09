<?php

/**
 * @file
 * Contains Drupal\cloudflare\Form\DefaultForm.
 */

namespace Drupal\cloudflare\Form;

use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class DefaultForm.
 *
 * @package Drupal\cloudflare\Form
 */
class CloudFlareCacheClearForm extends ConfigFormBase {

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
    $config = $this->config('cloudflare.settings');
    $form['path_clearing'] = [
      '#type' => 'fieldset',
      '#title' => t('Path Clear Cache'),
    ];

    $form['path_clearing']['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path\'s to clear'),
      '#description' => $this->t('You can enter multiple paths. One per line. Note CloudFlare\'s API only allows a max of ' . ZoneApi::MAX_PURGES_PER_REQUEST . ' path purges per request. It also does not handle wildcards at this time.'),
      '#default_value' => $config->get('apikey'),
    ];

    $form['path_clearing']['paths_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Clear Specified Paths'),
      '#submit' => ['purgePaths'],
      '#validate' => ['validatePathClear']
    ];

    $form['cache_clearing'] = [
      '#type' => 'fieldset',
      '#title' => t('Global Cache Clear'),
    ];

    $form['cache_clearing']['apikey'] = [
      '#type' => 'button',
      '#value' => $this->t('Clear Entire Cache'),
      '#description' => $this->t('Hold on to your butts.'),
      '#submit' => ['purgeEntireZone'],
    ];

    return parent::buildForm($form, $form_state);
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
    $exceeds_allowed_max_purges = $paths > ZoneApi::MAX_PURGES_PER_REQUEST;

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
    $api_key = $this->config->get('apikey');
    $email = $this->config->get('email');
    $zone = $this->config->get('zone');

    try{
      $this->zoneApi = new ZoneApi($api_key, $email);
      $this->zoneApi->purgeAllFiles($zone);
    }

    catch(CloudFlareHttpException $e) {
      $form_state->setErrorByName('CloudFlareHttpException', $e->getMessage());
    }

    catch(CloudFlareApiException $e) {
      $form_state->setErrorByName('CloudFlareApiException', $e->getMessage());
    }

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
    $api_key = $this->config->get('apikey');
    $email = $this->config->get('email');
    $zone = $this->config->get('zone');

    try{
      $this->zoneApi = new ZoneApi($api_key, $email);
      $this->zoneApi->purgeAllFiles($zone);
    }

    catch(CloudFlareHttpException $e) {
      $form_state->setErrorByName('CloudFlareHttpException', $e->getMessage());
    }

    catch(CloudFlareApiException $e) {
      $form_state->setErrorByName('CloudFlareApiException', $e->getMessage());
    }

  }

}
