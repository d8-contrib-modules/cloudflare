<?php

namespace Drupal\cloudflarepurger\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates a 'Cache-Tag' header in the format expected by CloudFlare.
 *
 * @see https://blog.cloudflare.com/introducing-a-powerful-way-to-purge-cache-on-cloudflare-purge-by-cache-tag/
 */
class CloudFlareCacheTagHeaderGenerator implements EventSubscriberInterface {
  /**
   * The CloudFlare Cache-Tag header limit in bytes.
   *
   * @var int
   */
  protected $limit;

  /**
   * Constructs a new CloudFlareCacheTagHeaderGenerator object.
   *
   * @param int $cloudflare_cache_tag_header_limit
   *   The CloudFlare Cache-Tag header limit in bytes.
   */
  public function __construct($cloudflare_cache_tag_header_limit) {
    $this->limit = $cloudflare_cache_tag_header_limit;
  }

  /**
   * Generates a 'Cache-Tag' header in the format expected by CloudFlare.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    // If there are no X-Drupal-Cache-Tags headers, then there is also no work
    // to be done.
    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    $cache_metadata = $response->getCacheableMetadata();
    $cache_tags = $cache_metadata->getCacheTags();
    $has_tags = !empty($cache_tags);
    if (!$has_tags) {
      return;
    }

    $response = $event->getResponse();

    $cloudflare_cachetag_header_value = static::drupalCacheTagsToCloudFlareCacheTag($cache_tags);

    // Hash each cache tag to make the header fit, at the cost of potentially
    // invalidating too much (cfr. hash collisions).
    $cache_tags = explode(',', $cloudflare_cachetag_header_value);
    $hashes = static::cacheTagsToHashes($cache_tags);
    $cloudflare_cachetag_header_value = implode(',', $hashes);

    $response->headers->set('Cache-Tag', $cloudflare_cachetag_header_value);
  }

  /**
   * Maps a Drupal X-Drupal-Cache-Tags header to a CloudFlare Cache-Tag header.
   *
   * @param string $drupal_cache_tags
   *   A X-Drupal-Cache-Tags header value, which has space-separated cache tags.
   *
   * @return string
   *   A CloudFlare Cache-Tag header, which has comma-separated cache tags.
   */
  protected static function drupalCacheTagsToCloudFlareCacheTag($drupal_cache_tags) {
    return implode(',', $drupal_cache_tags);
  }

  /**
   * Maps cache tags to hashes.
   *
   * Used when the Cache-Tag header exceeds CloudFlare's limit.
   *
   * @param string[] $cache_tags
   *   The cache tags in the header.
   *
   * @return string[]
   *   The hashes to use instead in the header.
   */
  public static function cacheTagsToHashes(array $cache_tags) {
    $hashes = [];
    foreach ($cache_tags as $cache_tag) {
      $hashes[] = substr(md5($cache_tag), 0, 3);
    }
    return $hashes;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
