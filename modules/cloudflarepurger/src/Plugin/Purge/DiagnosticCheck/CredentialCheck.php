<?php

namespace Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if valid Api credentials have been entered for CloudFlare.
 *
 * @PurgeDiagnosticCheck(
 *   id = "cloudflare_creds",
 *   title = @Translation("CloudFlare - Credentials"),
 *   description = @Translation("Checks to see if the supplied account credentials for CloudFlare are valid."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"cloudflare"}
 * )
 */
class CredentialCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {
  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a CredentialCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('cloudflare.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $has_valid_credentials = $this->config->get('valid_credentials');

    if (!$has_valid_credentials) {
      $this->recommendation = $this->t("Invalid Api credentials.");
      return SELF::SEVERITY_ERROR;
    }

    $this->recommendation = $this->t('Valid Api credentials detected.');
    return SELF::SEVERITY_OK;
  }

}
