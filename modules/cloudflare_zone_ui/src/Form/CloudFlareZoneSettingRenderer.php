<?php

/**
 * @file
 * Contains Drupal\cloudflare_zone_ui\CloudFlareSettingRenderer.
 */

namespace Drupal\cloudflare_zone_ui\Form;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBase;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBool;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMinify;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMobileRedirect;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecurityHeader;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSelectBase;
use CloudFlarePhpSdk\Exceptions\CloudFlareException;
use Psr\Log\LoggerInterface;

/**
 * Class CloudFlareZoneSettingRenderer.
 *
 * The purpose of this class is to render CloudFlarePhpSdk/ApiTypes/ZoneSettings
 * as Form API fields.
 */
class CloudFlareZoneSettingRenderer {
  use StringTranslationTrait;

  /**
   * The zone interface.
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
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor for CloudFlareZoneSettingRenderer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   The email validator.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config, CloudFlareZoneInterface $zone_api, LoggerInterface $logger) {
    $this->config = $config->get('cloudflare');
    $this->zoneApi = $zone_api;
    $this->logger = $logger;
  }

  /**
   * Builds a form render array for zone selection.
   *
   * @return array
   *   Form Api Render array for zone select.
   */
  public function buildZoneListing() {
    $form_select_field = [];
    $zones = $this->zoneApi->listZones();
    // Build the Zone Selector.
    try {
      $zone_select = [];
      foreach ($zones as $zone) {
        $zone_select[$zone->getZoneId()] = $zone->getName();
      }

      $form_select_field = [
        '#type' => 'select',
        '#title' => $this->t('Selected'),
        '#options' => $zone_select,
        '#description' => $this->t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
      ];
    }
    // If we were unable to get results back from the API attempt to provide
    // meaningful feedback to the user.
    catch (CloudFlareException $e) {
      $form_select_field['zone_api_exception'] = [
        '#type' => 'text',
        '#title' => $this->t($e->getMessage()),
      ];

      $this->logger->notice($e->getMessage());
    }
    // If all else fails.
    catch (\Exception $e) {
      $form_select_field['zone_api_exception'] = [
        '#type' => 'text',
        '#title' => $this->t($e->getMessage()),
      ];

      $this->logger->notice($e->getMessage());
    }
    return $form_select_field;
  }

  /**
   * Renders a user editable settings page for a zone.
   *
   * @param int|NULL $zone_id
   *   Id of the zone for which to retrieve setting.
   *
   * @return array
   *   Render array representing the zone settings.
   */
  public function renderZoneSettings($zone_id = NULL) {
    // Build the sortable table header.
    $header = [
      ZoneSettings::SETTING_WRAPPER_ID => $this->t('Setting Name'),
      ZoneSettings::SETTING_WRAPPER_VALUE => $this->t('Value'),
      ZoneSettings::SETTING_WRAPPER_EDITABLE => $this->t('Editable'),
      ZoneSettings::SETTING_WRAPPER_MODIFIED_ON => $this->t('Time Last Modified'),
    ];

    $tableselect_settings = array(
      '#type' => 'table',
      '#header' => $header,
      '#multiple' => FALSE,
    );

    $zone = $this->zoneApi->getZoneSettings();

    foreach ($zone->getSettings() as $zone_setting) {
      $setting_render = $this->renderSetting($zone_setting);

      if (!is_null($setting_render)) {
        $tableselect_settings[$zone_setting->getZoneSettingName()] = $setting_render;
      }
    }

    return $tableselect_settings;
  }

  /**
   * Renders an individual zone setting for display on the zone settings page.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBase $setting
   *   Zonesetting to render.
   *
   * @return array
   *   Rendered zone setting.
   */
  public function renderSetting(ZoneSettingBase $setting) {
    $type = get_class($setting);
    $row = [];

    switch ($type) {
      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBool':
        $value_render = $this->renderZoneSettingBool($setting);
        break;

      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBrowserCacheTtl':
      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingChallengeTtl':
      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecurityLevel':
      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSsl':
        $value_render = $this->renderZoneSettingSelectBase($setting);
        break;

      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMinify':
        $value_render = $this->renderZoneSettingMinify($setting);
        break;

      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMobileRedirect':
        $value_render = $this->renderZoneSettingMobileRedirect($setting);
        break;

      case 'ZoneSettingSecurityHeader':
        $value_render = $this->renderZoneSettingSecurityHeader($setting);
        break;
    }

    $row[ZoneSettings::SETTING_WRAPPER_ID] = [
      '#markup' => $setting->getZoneSettingName(),
    ];

    $row[ZoneSettings::SETTING_WRAPPER_VALUE] = [$value_render];

    $row[ZoneSettings::SETTING_WRAPPER_EDITABLE] = [
      '#markup' => ($setting->isEditable() ? 'Yes' : 'No'),
    ];

    $row[ZoneSettings::SETTING_WRAPPER_MODIFIED_ON] = [
      '#markup' => ($setting->getTimeModifiedOnServer() ? format_date($setting->getTimeModifiedOnServer()) : 'N/A'),
    ];

    return $row;
  }

  /**
   * Builds FormApi radio button for the value of a ZoneSettingBool.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBool $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array containing radio buttons.
   */
  private function renderZoneSettingBool(ZoneSettingBool $setting) {
    $setting = [
      '#type' => 'checkbox',
      '#default_value' => $setting->getValue(),
      '#disabled' => !($setting->isEditable()),
    ];

    return $setting;
  }

  /**
   * Builds FormApi TextField for the value of a ZoneSettingInt.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingInt $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array containing text field.
   */
  private function renderZoneSettingInt(ZoneSettingInt $setting) {
    $setting = [
      '#type' => 'textfield',
      '#default_value' => $setting->getValue(),
      '#size' => 20,
      '#disabled' => !($setting->isEditable()),
    ];

    return $setting;
  }

  /**
   * Builds FormApi radio button for the value of a ZoneSettingMinify.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMinify $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array containing radio button sets for js/css/html minify
   *   settings.
   */
  private function renderZoneSettingMinify(ZoneSettingMinify $setting) {
    $js_checkbox = [
      '#type' => 'checkbox',
      '#title' => 'JS',
      '#default_value' => $setting->isJsMinifyEnabled() ? '1' : '0',
      '#disabled' => !($setting->isEditable()),
    ];

    $css_checkbox = [
      '#type' => 'checkbox',
      '#title' => 'CSS',
      '#default_value' => $setting->isCssMinifyEnabled() ? '1' : '0',
      '#disabled' => !($setting->isEditable()),
    ];

    $html_checkbox = [
      '#type' => 'checkbox',
      '#title' => 'HTML',
      '#default_value' => $setting->isHtmlMinifyEnabled() ? '1' : '0',
      '#disabled' => !($setting->isEditable()),

    ];

    return [
      ZoneSettings::SETTING_MINIFY_CSS => $css_checkbox,
      ZoneSettings::SETTING_MINIFY_JS => $js_checkbox,
      ZoneSettings::SETTING_MINIFY_HTML => $html_checkbox,
    ];
  }

  /**
   * Builds MobileRedirect settings.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMobileRedirect $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array representation of ZoneSettingMobileRedirect
   */
  private function renderZoneSettingMobileRedirect(ZoneSettingMobileRedirect $setting) {
    $mobile_subdomain = $setting->getMobileSubdomain();
    $is_mobile_redirect_enabled = $setting->isIsMobileRedirectEnabled();
    $is_strip_uri_enabled = $setting->isIsStripUriEnabled();

    $mobile_subdomain_textfield = [
      '#type' => 'textfield',
      '#title' => 'Mobile Subdomain',
      '#default_value' => $mobile_subdomain,
      '#size' => 20,
      '#disabled' => !($setting->isEditable()),
    ];

    $mobile_redirect_checkbox = [
      '#type' => 'checkbox',
      '#title' => 'Mobile Redirect',
      '#default_value' => $is_mobile_redirect_enabled,
      '#disabled' => !($setting->isEditable()),
    ];

    $strip_uri_checkbox = [
      '#type' => 'checkbox',
      '#title' => 'Strip URI',
      '#default_value' => $is_strip_uri_enabled,
      '#disabled' => !($setting->isEditable()),
    ];

    return [
      $mobile_subdomain_textfield,
      $mobile_redirect_checkbox,
      $strip_uri_checkbox,
    ];
  }

  /**
   * Builds MobileRedirect settings.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecurityHeader $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array representation of ZoneSettingSecurityHeader
   */
  private function renderZoneSettingSecurityHeader(ZoneSettingSecurityHeader $setting) {
    $form = [];
    return $form;
  }

  /**
   * Builds FormApi select for the value of a ZoneSettingSelectBase.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSelectBase $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array containing select for settings.
   */
  private function renderZoneSettingSelectBase(ZoneSettingSelectBase $setting) {
    $select_options = $setting->validValues();
    $assoc_select_options = array_combine($select_options, $select_options);

    $form_select_field = [
      '#type' => 'select',
      '#title' => $this->t('Selected'),
      '#options' => $assoc_select_options,
      '#default_value' => $setting->getValue(),
      '#disabled' => !$setting->isEditable(),
    ];

    return [$setting->getZoneSettingName() => $form_select_field];
  }

}
