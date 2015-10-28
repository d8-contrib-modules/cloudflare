<?php

/**
 * @file
 * Contains Drupal\cloudflare_zone_ui\Form\CloudFlareZoneForm.
 */

namespace Drupal\cloudflare_zone_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\Exceptions\CloudFlareException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CloudFlareZoneForm.
 */
class CloudFlareZoneForm extends ConfigFormBase {

  /**
   * CloudFlare Zone Api interface.
   *
   * @var \Drupal\cloudflare\CloudFlareZoneInterface
   */
  protected $zoneApi;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new CloudFlareZoneForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   The email validator.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config, CloudFlareZoneInterface $zone_api, LoggerInterface $logger) {
    $this->configFactory = $config;
    $this->config = $config->get('cloudflare.settings');
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cloudflare.zone'),
      $container->get('logger.factory')->get('cloudflare')
    );
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

    $form['zone'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Zone Settings'),
    ];

    try {
      $cloudflare_renderer = new CloudFlareZoneSettingRenderer($this->configFactory, $this->zoneApi, $this->logger);
      $form['zone']['selected'] = $cloudflare_renderer->buildZoneListing();
      $zone_render = $cloudflare_renderer->renderZoneSettings();
      $form['zone']['table'] = $zone_render;
    }

    // If we were unable to get results back from the API attempt to provide
    // meaningful feedback to the user.
    catch (CloudFlareException $e) {
      drupal_set_message("Unable to connect to CloudFlare. " . $e->getMessage(), 'error');
      $form['zone']['#access'] = FALSE;
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
    $zone_settings = $this->zoneApi->getZoneSettings();

    foreach ($form_user_input as $setting_name => $value_wrapper) {
      $current_setting = $zone_settings->getSettingById($setting_name);
      $is_boolean = in_array($setting_name, ZoneSettings::getBooleanSettings());
      $is_integer = in_array($setting_name, ZoneSettings::getIntegerSettings());
      $is_select = in_array($setting_name, ZoneSettings::getSelectSettings());

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

      elseif ($is_select) {
        $new_value = $value_wrapper['value'][0][$setting_name];
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
        }
      }
    }

    $this->zoneApi->updateZoneSettings($zone_settings);
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
