<?php

/**
 * @file
 * Contains Drupal\cloudflare\Form\DefaultForm.
 */

namespace Drupal\cloudflare\Form;

use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cloudflare\CloudFlareZoneSettingRenderer;


/**
 * Class DefaultForm.
 *
 * @package Drupal\cloudflare\Form
 */
class CloudFlareZoneForm extends ConfigFormBase {

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
    return 'cloudflare_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['zone'] = [
      '#type' => 'fieldset',
      '#title' => t('Zone Settings'),
    ];

    try {
      $cloudflare_renderer = new CloudFlareZoneSettingRenderer();
    }

    // If we were unable to get results back from the API attempt to provide
    // meaningful feedback to the user.
    catch (CloudFlareHttpException $e) {
      $form['zone']['#description'] = $this->t($e->getMessage());
      return parent::buildForm($form, $form_state);
    }

    catch (CloudFlareApiException $e) {
      $form['zone']['#description'] = $this->t($e->getMessage());
      return parent::buildForm($form, $form_state);
    }

    $form['zone']['selected'] = $cloudflare_renderer->buildZoneListing();
    $zone_render = $cloudflare_renderer->renderZoneSettings();
    $form['zone']['table'] = $zone_render;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $is_email_valid = \Drupal::service('email.validator')->isValid($email);

    if (!$is_email_valid) {
      $form_state->setErrorByName('email', $this->t('Invalid Email Address.  Please enter a valid email address.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('cloudflare.settings')
      ->set('apikey', $form_state->getValue('apikey'))
      ->set('email', $form_state->getValue('email'))
      ->set('zone', $form_state->getValue('zone'))
      ->save();
  }

}
