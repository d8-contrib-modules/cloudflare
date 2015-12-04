<?php

/**
 * @file
 * Contains Drupal\cloudflare\EventSubscriber\ClientIpRestore.
 */

namespace Drupal\cloudflare\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Psr\Log\LoggerInterface;

/**
 * Restores the true client Ip address.
 *
 * @see https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-CloudFlare-handle-HTTP-Request-headers-
 */
class ClientIpRestore implements EventSubscriberInterface {
  use StringTranslationTrait;

  const CLOUDFLARE_RANGE_KEY = 'cloudflare_range_key';
  const CLOUDFLARE_CLIENT_IP_RESTORE_ENABLED = 'client_ip_restore_enabled';
  const IPV4_ENDPOINTS_URL = 'https://www.cloudflare.com/ips-v4';
  const IPV6_ENDPOINTS_URL = 'https://www.cloudflare.com/ips-v6';

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TRUE/FALSE if client ip restoration enabled.
   *
   * @var bool
   */
  protected $isClientIpRestoreEnabled;

  /**
   * Constructs a UpdateFetcher.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config, CacheBackendInterface $cache, ClientInterface $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->config = $config->get('cloudflare.settings');
    $this->logger = $logger;
    $this->isClientIpRestoreEnabled = $this->config->get(SELF::CLOUDFLARE_CLIENT_IP_RESTORE_ENABLED);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequest', 20);
    return $events;
  }

  /**
   * Restores the origination client IP delivered to Drupal from CloudFlare.
   */
  public function onRequest(GetResponseEvent $event) {
    // @ignore Drupal.Semantics.RemoteAddress.RemoteAddress:function
    if (!$this->isClientIpRestoreEnabled) {
      return;
    }

    $has_http_cf_connecting_ip = !empty($_SERVER['HTTP_CF_CONNECTING_IP']);
    if (!$has_http_cf_connecting_ip) {
      $this->logger->warning($this->t("Request came through without being routed through CloudFlare."));
      return;
    }

    $cloudflare_ipranges = $this->getCloudFlareIpRanges();
    $has_ip_already_changed = $_SERVER['REMOTE_ADDR'] == $_SERVER['HTTP_CF_CONNECTING_IP'];
    $request_originating_from_cloudflare = IpUtils::checkIp($_SERVER['REMOTE_ADDR'], $cloudflare_ipranges);

    if ($has_http_cf_connecting_ip && !$request_originating_from_cloudflare) {
      $this->logger->warning($this->t("REMOTE_ADDR does not match a known CloudFlare IP and there is HTTP_CF_CONNECTING_IP.  Someone is attempting to mask their IP address by setting HTTP_CF_CONNECTING_IP."));
      return;
    }

    // Some environments may make the alteration for us. In which case no
    // action is required.
    if ($has_ip_already_changed) {
      $link_to_settings = Url::fromRoute('cloudflare.admin_settings_form')->toString();
      $message = $this->t('Request has already been updated.  This should be deactivated. Please go <a href="@link_to_settings">here</a> to disable "Restore Client Ip Address".', ['@link_to_settings' => $link_to_settings]);
      $this->logger->error($message);
      return;
    }

    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
  }

  /**
   * Get a list of cloudflare IP Ranges.
   */
  public function getCloudFlareIpRanges() {
    if ($cache = $this->cache->get($this->CLOUDFLARE_RANGE_KEY)) {
      return $cache->data;
    }

    try {
      $ipv4_raw_listings = (string) $this->httpClient
        ->get(SELF::IPV4_ENDPOINTS_URL)
        ->getBody();

      $ipv6_raw_listings = (string) $this->httpClient
        ->get(SELF::IPV6_ENDPOINTS_URL)
        ->getBody();

      $iv4_endpoints = explode("\n", $ipv4_raw_listings);
      $iv6_endpoints = explode("\n", $ipv6_raw_listings);
      $cloudflare_ips = array_combine($iv4_endpoints, $iv6_endpoints);
      $cloudflare_ips = array_map('trim', $cloudflare_ips);
      $this->cache->set($this->CLOUDFLARE_RANGE_KEY, $cloudflare_ips, Cache::PERMANENT);
      return $cloudflare_ips;
    }
    catch (RequestException $exception) {
      $this->logger->error($exception->getMessage());
    }
  }

}
