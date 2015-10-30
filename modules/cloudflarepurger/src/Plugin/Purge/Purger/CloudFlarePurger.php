<?php

/**
 * @file
 * Contains \Drupal\cloudflare\Plugin\PurgePurger\CloudFlarePurger.
 */

namespace Drupal\cloudflarepurger\Plugin\Purge\Purger;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\cloudflarepurger\EventSubscriber\CloudFlareCacheTagHeaderGenerator;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use CloudFlarePhpSdk\ApiEndpoints\CloudFlareAPI;
use CloudFlarePhpSdk\ApiEndpoints\ZoneApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * CloudFlare purger.
 *
 * @PurgePurger(
 *   id = "cloudflare",
 *   label = @Translation("CloudFlare"),
 *   description = @Translation("Purger for CloudFlare."),
 *   configform = "Drupal\cloudflare\Form\CloudFlareAdminSettingsForm",
 *   types = {"tag", "url", "everything"},
 *   multi_instance = FALSE,
 * )
 */
class CloudFlarePurger extends PurgerBase implements PurgerInterface {

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
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * ZoneApi object for interfacing with CloudFlare Php Sdk.
   *
   * @var \CloudFlarePhpSdk\ApiEndpoints\ZoneApi
   */
  protected $zoneApi;

  /**
   * The current cloudflare ZoneId.
   *
   * @var string
   */
  protected $zone;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cloudflare.state'),
      $container->get('logger.factory')->get('cloudflare')
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\CloudFlarePurger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks limits associated with CloudFlare Api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   *
   * @throws \LogicException
   *   Thrown if $configuration['id'] is missing, see Purger\Service::createId.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, CloudFlareStateInterface $state, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = $config->get('cloudflare.settings');
    $this->state = $state;
    $this->logger = $logger;

    // This is a unique case where the ApiSdk is being accessed directly and not
    // via a service.  Purging should only ever happen through the purge module
    // which is why this is NOT in a service.
    $api_key = $this->config->get('apikey');
    $email = $this->config->get('email');
    $this->zone = $this->config->get('zone');
    $this->zoneApi = new ZoneApi($api_key, $email);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(InvalidationInterface $invalidation) {
    $this->invalidateMultiple([$invalidation]);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $invalidations) {
    $chunks = array_chunk($invalidations, CloudFlareAPI::MAX_TAG_PURGES_PER_REQUEST);
    foreach ($chunks as $chunk) {
      $this->purgeChunk($chunk);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    return 0.2;
  }

  /**
   * Purges a chunk of tags.
   *
   * Integration point between purge and CloudFlareAPI.  Purge requires state
   * tracking on each item purged.  This function provides that accounting and
   * calls CloudflareApi.
   *
   * CloudFlare only allows us to purge 30 tags at once.
   *
   * @param array $invalidations
   *   Chunk of purge module invalidation objects to purge via CloudFlare.
   */
  private function purgeChunk(array &$invalidations) {
    $api_targets_to_purge = [];

    // This method is unfortunately a bit verbose due to the fact that we
    // need to update the purge states as we proceed.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $api_targets_to_purge[] = $invalidation->getExpression();
    }

    try {
      // Interface with the CloudFlarePhpSdk.
      $invalidation_type = $invalidations[0]->getPluginId();
      if ($invalidation_type == 'tag') {
        // @todo Remove this wrapper once CloudFlare supports 16k headers.
        // Also invalidate the cache tags as hashes, to automatically also work
        // for responses that exceed CloudFlare's Cache-Tag header limit.
        $hashes = CloudFlareCacheTagHeaderGenerator::cacheTagsToHashes($api_targets_to_purge);
        $tags = Cache::mergeTags($api_targets_to_purge, $hashes);

        $this->zoneApi->purgeTags($this->zone, $tags);
        $this->state->incrementTagPurgeDailyCount();
      }

      elseif ($invalidation_type == 'url') {
        $this->zoneApi->purgeIndividualFiles($this->zone, $api_targets_to_purge);
      }

      elseif ($invalidation_type == 'everything') {
        $this->zoneApi->purgeAllFiles($this->zone);
      }

      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }

    catch (\Exception $e) {
      foreach ($invalidations as $invalidation) {
        $this->logger->error($e->getMessage());
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }

    finally {
      $this->state->incrementApiRateCount();
    }
  }

}
