<?php

namespace Drupal\cloudflare\EventSubscriber;

use Drupal\Core\Url;
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
  const CLOUDFLARE_BYPASS_HOST = 'bypass_host';
  const IPV4_ENDPOINTS_URL = 'https://www.cloudflare.com/ips-v4';
  const IPV6_ENDPOINTS_URL = 'https://www.cloudflare.com/ips-v6';

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
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
   * Constructs a ClientIpRestore.
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
    $this->bypassHost = $this->config->get(SELF::CLOUDFLARE_BYPASS_HOST);
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
    if (!$this->isClientIpRestoreEnabled) {
      return;
    }
    $current_request = $event->getRequest();
    $cf_connecting_ip = $current_request->server->get('HTTP_CF_CONNECTING_IP');
    $has_http_cf_connecting_ip = !empty($cf_connecting_ip);
    $has_bypass_host = !empty($this->bypassHost);
    $client_ip = $current_request->getClientIp();
    $incoming_uri = $current_request->getHost();
    $request_expected_to_bypass_cloudflare = $has_bypass_host && $this->bypassHost == $incoming_uri;

    if ($request_expected_to_bypass_cloudflare) {
      return;
    }

    if (!$has_http_cf_connecting_ip) {
      $message = $this->t("Request came through without being routed through CloudFlare.");
      $this->logger->warning($message);
      return;
    }

    $has_ip_already_changed = $client_ip == $cf_connecting_ip;

    // Some environments may make the alteration for us. In which case no
    // action is required.
    if ($has_ip_already_changed) {
      $url_to_settings = Url::fromRoute('cloudflare.admin_settings_form');
      $link_to_settings = $url_to_settings->getInternalPath();
      $message = $this->t('Request has already been updated.  This functionality should be deactivated. Please go <a href="@link_to_settings">here</a> to disable "Restore Client Ip Address".', ['@link_to_settings' => $link_to_settings]);
      $this->logger->warning($message);
      return;
    }

    $cloudflare_ipranges = $this->getCloudFlareIpRanges();
    $request_originating_from_cloudflare = IpUtils::checkIp($client_ip, $cloudflare_ipranges);

    if ($has_http_cf_connecting_ip && !$request_originating_from_cloudflare) {
      $message = $this->t("Client IP of @client_ip does not match a known CloudFlare IP but there is HTTP_CF_CONNECTING_IP of @cf_connecting_ip.", ['@cf_connecting_ip' => $cf_connecting_ip, '@client_ip' => $client_ip]);
      $this->logger->warning($message);
      return;
    }

    $event->getRequest()->server->set('REMOTE_ADDR', $cf_connecting_ip);
    $event->getRequest()->overrideGlobals();
  }

  /**
   * Get a list of cloudflare IP Ranges.
   *
   * @return array
   *   Listing of the CloudFlareIP edge server IP ranges
   */
  public function getCloudFlareIpRanges() {
    if ($cache = $this->cache->get(self::CLOUDFLARE_RANGE_KEY)) {
      return $cache->data;
    }

    try {
      $ipv4_raw_listings = trim((string) $this->httpClient
        ->get(SELF::IPV4_ENDPOINTS_URL)
        ->getBody());

      $ipv6_raw_listings = trim((string) $this->httpClient
        ->get(SELF::IPV6_ENDPOINTS_URL)
        ->getBody());

      $iv4_endpoints = explode("\n", $ipv4_raw_listings);
      $iv6_endpoints = explode("\n", $ipv6_raw_listings);
      $cloudflare_ips = array_merge($iv4_endpoints, $iv6_endpoints);
      $cloudflare_ips = array_map('trim', $cloudflare_ips);

      if (empty($cloudflare_ips)) {
        $this->logger->error("Unable to get a listing of CloudFlare IPs.");
        return [];
      }
      $this->cache->set(SELF::CLOUDFLARE_RANGE_KEY, $cloudflare_ips, Cache::PERMANENT);
      return $cloudflare_ips;
    }
    catch (RequestException $exception) {
      $this->logger->error("Unable to get a listing of CloudFlare IPs. " . $exception->getMessage());
    }
  }

}
