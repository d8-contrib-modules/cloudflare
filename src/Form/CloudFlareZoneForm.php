<?php

/**
 * @file
 * Contains Drupal\cloudflare\Form\DefaultForm.
 */

namespace Drupal\cloudflare\Form;

use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;

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
    $this->config = \Drupal::config('cloudflare.settings');

    $form['zone'] = [
      '#type' => 'fieldset',
      '#title' => t('Zone Settings'),
    ];

    try {
      $cloudflare_renderer = new CloudFlareZoneSettingRenderer();
      $form['zone']['selected'] = $cloudflare_renderer->buildZoneListing();
      $zone_render = $cloudflare_renderer->renderZoneSettings();
      $form['zone']['table'] = $zone_render;
    }

    // If we were unable to get results back from the API attempt to provide
    // meaningful feedback to the user.
    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to connect to CloudFlare. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      $form['zone']['#access'] = FALSE;
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to connect to CloudFlare. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

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
    $form_user_input = $form_state->getValue('table');
    $zone_id = $form_state->getValue('selected');

    // @todo.  Might be worth saving this in the form_state so we don't need
    // to load this 2x.
    $this->config = \Drupal::config('cloudflare.settings');
    $zone_api = new ZoneApi($this->config->get('apikey'), $this->config->get('email'));
    $zone_settings = $zone_api->getZoneSettings($zone_id);

    foreach ($form_user_input as $setting_name => $value_wrapper) {
      $current_setting = $zone_settings->getSettingById($setting_name);
      $is_boolean = in_array($setting_name, ZoneSettings::getBooleanSettings());
      $is_integer = in_array($setting_name, ZoneSettings::getIntegerSettings());

      // If the setting is not editable then there is nothing to change on the
      // API.
      if (!$current_setting->isEditable()) {
        continue;
      }

      if ($is_boolean) {
        $new_value = $value_wrapper['value'][0];
        $value_changed = $new_value != $current_setting->getValue();
        if ($value_changed) {
          $current_setting->setValue($new_value ? 'on' : 'off');
        }
      }

      elseif ($is_integer) {
        $new_value = $value_wrapper['value'][0];
        $value_changed = $new_value != $current_setting->getValue();
        if ($value_changed) {
          $current_setting->setValue($new_value);
        }
      }

      // The remaining types are specific one offs that have additional logic
      // which cannot be accommodated by ZoneSettingBool or ZoneSettingInt.
      else {
        switch ($setting_name) {
          case ZoneSettings::SETTING_MINIFY:
            $new_css = $value_wrapper['value'][0][ZoneSettings::SETTING_MINIFY_CSS];
            $new_js = $value_wrapper['value'][0][ZoneSettings::SETTING_MINIFY_JS];
            $new_html = $value_wrapper['value'][0][ZoneSettings::SETTING_MINIFY_HTML];

            $old_css = $current_setting->isCssMinifyEnabled();
            $old_js = $current_setting->isJsMinifyEnabled();
            $old_html = $current_setting->isHtmlMinifyEnabled();

            $value_changed = ($new_css != $old_css) || ($new_js != $old_js) || ($new_html != $old_html);

            if ($value_changed) {
              $current_setting->setValue($new_css, $new_html, $new_js);
            }
            break;

          // @todo this has not been wired up yet.
          case ZoneSettings::SETTING_MOBILE_REDIRECT:
            $is_mobile_redirect_enabled = (bool) $value[ZoneSettings::SETTING_MOBILE_REDIRECT_ENABLED];
            $mobile_subdomain = $value[ZoneSettings::SETTING_MOBILE_REDIRECT_MOBILE_SUBDOMAIN];
            $is_strip_uri_enabled = (bool) $value[ZoneSettings::SETTING_MOBILE_REDIRECT_STRIP_URI];
            break;

          // @todo this has not been wired up yet.
          case ZoneSettings::SETTING_SECURITY_HEADER:
            break;

          case ZoneSettings::SETTING_BROWSER_CACHE_TTL:
          case ZoneSettings::SETTING_CHALLENGE_TTL:
          case ZoneSettings::SETTING_SECURITY_LEVEL:
          case ZoneSettings::SETTING_SSL:
            $new_value = $value_wrapper['value'][0][$setting_name];
            $value_changed = $new_value != $current_setting->getValue();
            if ($value_changed) {
              $current_setting->setValue($new_value);
            }
            break;

        }
      }
    }

    try {
      $zone_api->updateZone($zone_settings);
    }

    catch (CloudFlareHttpException $e) {
      drupal_set_message("Unable to connect to CloudFlare. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      $form['zone']['#access'] = FALSE;
      return;
    }

    catch (CloudFlareApiException $e) {
      drupal_set_message("Unable to connect to CloudFlare. " . $e->getMessage(), 'error');
      \Drupal::logger('cloudflare')->error($e->getMessage());
      return;
    }

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
