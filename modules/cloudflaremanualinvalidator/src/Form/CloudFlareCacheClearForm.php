<?php

/**
 * @file
 * Contains Drupal\cloudflaremanualinvalidator\Form\CloudFlareCacheClearForm.
 *
 * @todo this functionality should be added to the purge module. This is
 * a place-holder till that happens.  It was useful before we had purge
 * integration.
 */

namespace Drupal\cloudflaremanualinvalidator\Form;

use Drupal\cloudflare\Purger;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;

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
      '#description' => $this->t('You can enter multiple paths. One per line. <br />Paths must be absolute.  Relative paths not currently supported.<br />  Note CloudFlare\'s API only allows a max of ' . ZoneApi::MAX_PURGES_PER_REQUEST . ' path purges per request. It also does not handle wildcards at this time.'),
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
      '#value' => $this->t('Clear Entire Cache'),
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
    $cloudflare_config = \Drupal::service('cloudflare.config');
    $cloudflare_purger = new Purger($cloudflare_config);
    $cloudflare_purger->invalidateZone();
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
    $paths = $form_state->getValue('paths');

    // Remove extra-whitespace.
    $paths = str_replace(' ', '', $paths);

    $arr_paths = explode(PHP_EOL, $paths);
    $cloudflare_config = \Drupal::service('cloudflare.config');
    $cloudflare_purger = new Purger($cloudflare_config);
    $cloudflare_purger->invalidateByPath($arr_paths);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo figure out how to replace formbase so that this is not necessary.
  }

}
