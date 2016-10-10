<?php

namespace Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use CloudFlarePhpSdk\ApiEndpoints\CloudFlareAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks that the site is within CloudFlare's daily tag purge rate limit.
 *
 * CloudFlare currently has a rate limit of 2000 tag purges/day.
 *
 * @todo We hope this limit is raised soon!
 *
 * @see https://support.cloudflare.com/hc/en-us/articles/206596608-How-to-Purge-Cache-Using-Cache-Tags
 *
 * @PurgeDiagnosticCheck(
 *   id = "cloudflare_daily_limit_check",
 *   title = @Translation("CloudFlare - Daily Tag Purge Limit"),
 *   description = @Translation("Checks that the site is not violating CloudFlare's daily purge limit."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"cloudflare"}
 * )
 */
class DailyTagPurgeLimitCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * Flag for if dependencies for CloudFlare are met.
   *
   * @var bool
   */
  protected $areCloudFlareComposerDependenciesMet;

  /**
   * Constructs a DailyTagPurgeLimitCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param bool $composer_dependencies_met
   *   Checks that the composer dependencies for CloudFlare are met.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CloudFlareStateInterface $state, $composer_dependencies_met) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->areCloudFlareComposerDependenciesMet = $composer_dependencies_met;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cloudflare.state'),
      $container->get('cloudflare.composer_dependency_check')->check()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->areCloudFlareComposerDependenciesMet) {
      $this->recommendation = $this->t("Composer dependencies unmet.  Unable to assess API rate limits.");
      return SELF::SEVERITY_ERROR;
    }

    // Current number of purges today.
    $daily_count = $this->state->getTagDailyCount();
    $this->value = $daily_count;

    // Warn at 75% of capacity.
    $daily_warning_level = .75 * CloudFlareAPI::API_TAG_PURGE_DAILY_RATE_LIMIT;

    $message_variables = [
      ':daily_limit' => CloudFlareAPI::API_TAG_PURGE_DAILY_RATE_LIMIT,
      ':$daily_count' => $daily_count,
    ];

    if ($daily_count >= CloudFlareAPI::API_TAG_PURGE_DAILY_RATE_LIMIT) {
      $this->recommendation = $this->t('Past Api limit of :daily_count/:daily_limit limit tag purges/day.', $message_variables);
      return SELF::SEVERITY_ERROR;
    }

    elseif ($daily_count >= $daily_warning_level) {
      $this->recommendation = $this->t('Approaching Api limit of :daily_count/:daily_limit limit tag purges/day.', $message_variables);
      return SELF::SEVERITY_WARNING;
    }

    elseif ($daily_count < $daily_warning_level) {
      $this->recommendation = $this->t('Site is safely below the daily limit of :daily_limit tag purges/day.', $message_variables);
      return SELF::SEVERITY_OK;
    }
  }

}
