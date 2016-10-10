<?php

namespace Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks that the CloudFlareApi Composer Dependency is installed on the system.
 *
 * Prevents Purge module from attempting to purge.
 *
 * @PurgeDiagnosticCheck(
 *   id = "composer_dependencies_check",
 *   title = @Translation("CloudFlare - Composer Dependencies Check."),
 *   description = @Translation("Checks that the Composer dependencies For CloudFlare have been met."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"cloudflare"}
 * )
 */
class ComposerDependenciesCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {
  /**
   * Flag for if dependencies for CloudFlare are met.
   *
   * @var bool
   */
  protected $areCloudFlareComposerDependenciesMet;

  /**
   * Constructs a ComposerDependenciesCheck diagnostic check object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks that the composer dependencies for CloudFlare are met.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->areCloudFlareComposerDependenciesMet = $check_interface->check();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cloudflare.composer_dependency_check')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->areCloudFlareComposerDependenciesMet) {
      $this->recommendation = CloudFlareComposerDependenciesCheckInterface::ERROR_MESSAGE;
      return SELF::SEVERITY_ERROR;
    }

    else {
      $this->recommendation = $this->t('Composer dependencies have been met.');
      return SELF::SEVERITY_OK;
    }
  }

}
