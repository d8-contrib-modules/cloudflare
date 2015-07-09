<?php

/**
 * @file
 * Contains Drupal\cloudflare\CloudFlareSettingRenderer.
 */

namespace Drupal\cloudflare;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBool;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingInt;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMinify;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMobileRedirect;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettings;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecurityHeader;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecurityLevel;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecuritySsl;
use CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBase;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use CloudFlarePhpSdk\Exceptions\CloudFlareHttpException;
use CloudFlarePhpSdk\Exceptions\CloudFlareApiException;

/**
 * Class CloudFlareZoneSettingRenderer.
 *
 * The purpose of this class is to render CloudFlarePhpSdk/ApiTypes/ZoneSettings
 * as Form API fields.
 */
class CloudFlareZoneSettingRenderer {
  private $zoneApi;
  private $config;
  private $zones;

  /**
   * Constructor for CloudFlareZoneSettingRenderer.
   */
  public function __construct() {
    $this->config = \Drupal::config('cloudflare.settings');
    $this->zoneApi = new ZoneApi($this->config->get('apikey'), $this->config->get('email'));
    $this->zones = $this->zoneApi->listZones();
  }

  /**
   * Builds a form render array for zone selection.
   * @return array
   *   Form Api Render array for zone select.
   */
  public function buildZoneListing() {
    $form_select_field = [];

    // Build the Zone Selector.
    try {
      $zone_select = [];
      foreach ($this->zones as $zone) {
        $zone_select[$zone->getZoneId()] = $zone->getName();
      }

      $form_select_field = [
        '#type' => 'select',
        '#title' => t('Selected'),
        '#options' => $zone_select,
        '#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
      ];
    }
    // If we were unable to get results back from the API attempt to provide
    // meaningful feedback to the user.
    catch (CloudFlareHttpException $e){
      $form_select_field['zone_api_exception'] = [
        '#type' => 'text',
        '#title' => $this->t($e->getMessage()),
      ];

      \Drupal::logger('cloudflare')->notice($e->getMessage(), WATCHDOG_ERROR);
    }

    catch (CloudFlareApiException $e) {
      $form_select_field['zone_api_exception'] = [
        '#type' => 'text',
        '#title' => $this->t($e->getMessage()),
      ];
      \Drupal::logger('cloudflare')->notice($e->getMessage(), WATCHDOG_ERROR);
    }

    // If all else fails.
    catch (\Exception $e) {
      $form_select_field['zone_api_exception'] = [
        '#type' => 'text',
        '#title' => $this->t($e->getMessage()),
      ];

      \Drupal::logger('cloudflare')->notice($e->getMessage(), WATCHDOG_ERROR);
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
    if (is_null($zone_id)) {
      $zone_id = $this->zones[0]->getZoneId();
    }

    $zone = $this->zoneApi->getZoneSettings($zone_id);
    $render_array = [];
    foreach ($zone->getSettings() as $zone_setting) {
      $setting_render = $this->renderSetting($zone_setting);

      if (!is_null($setting_render)) {
        $render_array[$zone_setting->getZoneSettingName()] = $setting_render;
      }
    }

    // Build the sortable table header.
    $header = [
      ZoneSettings::SETTING_WRAPPER_ID => t('Setting Name'),
      ZoneSettings::SETTING_WRAPPER_VALUE => t('Value'),
      ZoneSettings::SETTING_WRAPPER_EDITABLE => t('Editable'),
      ZoneSettings::SETTING_WRAPPER_MODIFIED_ON => t('Time Last Modified'),
    ];

    $tableselect_settings = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $render_array,
      '#multiple' => FALSE,
    );

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
    $current_type = get_class($setting);
    $render_result = [];

    switch ($current_type) {
      case 'CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBool':
        $value_render = ['data' => $this->renderZoneSettingBool($setting)];
        break;

      case 'ZoneSettingInt':
        $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $this->renderZoneSettingInt($setting);
        break;

      case 'ZoneSettingMinify':
        $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $this->renderZoneSettingMinify($setting);
        break;

      case 'ZoneSettingMobileRedirect':
        $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $this->renderZoneSettingMobileRedirect($setting);
        break;

      case 'ZoneSettingSecurityHeader':
        $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $this->renderZoneSettingSecurityHeader($setting);
        break;

      case 'ZoneSettingSecurityLevel':
        $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $this->renderZoneSettingSecurityLevel($setting);
        break;

      case 'ZoneSettingSsl':
        $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $this->renderZoneSettingSsl($setting);
        break;
    }

    if (is_null($value_render)) {
      return;
    }

    $render_result[ZoneSettings::SETTING_WRAPPER_ID] = ['data' => [
      '#markup' => $setting->getZoneSettingName()
    ]];

    $render_result[ZoneSettings::SETTING_WRAPPER_VALUE] = $value_render;

    $render_result[ZoneSettings::SETTING_WRAPPER_EDITABLE] = ['data' => [
      '#markup' => ($setting->isEditable() ? 'Yes' : 'No'),
    ]];

    $render_result[ZoneSettings::SETTING_WRAPPER_MODIFIED_ON] = ['data' => [
      '#markup' => ($setting->getTimeModifiedOnServer() ? $setting->getTimeModifiedOnServer() : 'N/A'),
    ]];

    return $render_result;
  }

  /**
   * Builds FormApi radio button for the value of a ZoneSettingBool.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingBool $setting
   *   The settings to render
   *
   * @return array
   *   FormApi render array containing radio buttons.
   */
  private function renderZoneSettingBool(ZoneSettingBool $setting) {
    $active = [0 => t('Yes'), 1 => t('No')];
    $setting = [
      '#type' => 'checkboxes',
      '#title' => t('Autocomplete matching'),
      '#default_value' => 'STARTS_WITH',
      '#options' => array(
        'STARTS_WITH' => t('Starts with'),
        'CONTAINS' => t('Contains'),
      ),
      '#description' => 'blah'
    ];

    return $setting;
  }


  /**
   * Builds FormApi TextField for the value of a ZoneSettingInt.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingInt $setting
   *   The settings to render
   *
   * @return array
   *   FormApi render array containing text field.
   */
  private function renderZoneSettingInt(ZoneSettingInt $setting) {

  }

  /**
   * Builds FormApi radio button for the value of a ZoneSettingMinify.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingMinify $setting
   *   The settings to render
   *
   * @return array
   *   FormApi render array containing radio button sets for js/css/html minify
   *   settings.
   */
  private function renderZoneSettingMinify(ZoneSettingMinify $setting) {

  }


  private function renderZoneSettingMobileRedirect() {

  }

  private function renderZoneSettingSecurityHeader() {

  }

  /**
   * Builds FormApi select for the value of a ZoneSettingSecurityLevel.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSecurityLevel $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array containing select for ZoneSettingSecurityLevel
   *   settings.
   */
  private function renderZoneSettingSecurityLevel(ZoneSettingSecurityLevel $setting) {

  }

  /**
   * Builds FormApi select for the value of a ZoneSettingSsl.
   *
   * @param \CloudFlarePhpSdk\ApiTypes\Zone\ZoneSettingSsl $setting
   *   The settings to render.
   *
   * @return array
   *   FormApi render array containing select for ZoneSettingSsl settings.
   */
  private function renderZoneSettingSsl(ZoneSettingSsl $setting) {

  }

}
