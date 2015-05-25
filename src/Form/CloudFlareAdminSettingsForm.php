<?php

/**
 * @file
 * Contains Drupal\cloudflare\Form\DefaultForm.
 */

namespace Drupal\cloudflare\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cloudflare;

/**
 * Class DefaultForm.
 *
 * @package Drupal\cloudflare\Form
 */
class CloudFlareAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cloudflare.default_config'
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


    $config = $this->config('cloudflare.default_config');
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('blah'),
      '#default_value' => $config->get('name'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('cloudflare.default_config')
      ->set('name', $form_state->getValue('name'))
      ->save();
  }

}
