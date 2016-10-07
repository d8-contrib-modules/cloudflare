<?php

namespace Drupal\cloudflare\Wizard;

use Drupal\ctools\Wizard\FormWizardBase;

/**
 * Class ConfigWizard used for entering CloudFlare config.
 *
 * The wizard apperoach is used because a user first must enter their
 * credentials before selecting a zone. The different pages of a wizard
 * supports this workflow.
 */
class ConfigWizard extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('CloudFlare Config');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('CloudFlare Config');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'cloudflare.admin_settings_form.step';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    return [
      'one' => [
        'form' => 'Drupal\cloudflare\Form\SettingsForm',
        'title' => $this->t('CloudFlare Settings'),
      ],
      'two' => [
        'form' => 'Drupal\cloudflare\Form\ZoneSelectionForm',
        'title' => $this->t('CloudFlare Zone Selection'),
      ],
    ];
  }

}
