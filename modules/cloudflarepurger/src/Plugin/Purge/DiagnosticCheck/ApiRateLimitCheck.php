<?php

/**
 * @file
 * Contains \Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\ApiRateLimitCheck.
 */

namespace Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use CloudFlarePhpSdk\ApiEndpoints\CloudFlareAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks that the site is within CloudFlare's API rate limits.
 *
 * CloudFlare currently has a rate limit of 1200 Api calls every 5 minutes.
 *
 * @see https://api.cloudflare.com/#requests
 *
 * @PurgeDiagnosticCheck(
 *   id = "cloudflare_api_rate_limit_check",
 *   title = @Translation("CloudFlare - Api Rate limit check."),
 *   description = @Translation("Checks that the site is not violating CloudFlare's Api purge limit."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"cloudflare"}
 * )
 */
class ApiRateLimitCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * Constructs a CloudFlareApiRateLimitCheck diagnostic check object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CloudFlareStateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cloudflare.state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Current number of purges today.
    $rate_count = $this->state->getApiRateCount();
    $this->value = $rate_count;

    // Warn at 75% of capacity.
    $daily_warning_level = .75 * CloudFlareAPI::API_RATE_LIMIT;

    $message_variables = [
      ':rate_limit' => CloudFlareAPI::API_RATE_LIMIT,
      ':$rate_count' => $rate_count,
    ];

    if ($rate_count > CloudFlareAPI::API_TAG_PURGE_DAILY_RATE_LIMIT) {
      $this->recommendation = $this->t('Exceeded Api limit of :$rate_count/:rate_limit limit purges/day.', $message_variables);
      return SELF::SEVERITY_ERROR;
    }

    elseif ($rate_count >= $daily_warning_level) {
      $this->recommendation = $this->t('Approaching Api limit of :$rate_count/:rate_limit limit purges/day.', $message_variables);
      return SELF::SEVERITY_WARNING;
    }

    elseif ($rate_count < $daily_warning_level) {
      $this->recommendation = $this->t('Site is safely below the rate limit of :rate_limit every 5 minutes.', $message_variables);
      return SELF::SEVERITY_OK;
    }
  }

}
