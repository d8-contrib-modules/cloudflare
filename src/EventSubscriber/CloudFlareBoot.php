<?php
namespace Drupal\cloudflare\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a CloudFlareBoot.
 */
class CloudFlareBoot implements EventSubscriberInterface {


  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('CloudFlareLoad', 20);
    return $events;
  }

  public function CloudFlareLoad(GetResponseEvent $event) {
    $reverse_proxy_header = $event->getRequest()
      ->getTrustedHeaderName(Request::HEADER_CLIENT_IP);
    $forwarded = $event->getRequest()->headers->get($reverse_proxy_header);
    $clientIps = array_map('trim', explode(',', $forwarded));

    $trustedProxies = $event->getRequest()->getTrustedProxies();

    foreach ($clientIps as $ip) {
      if (!in_array($ip, $trustedProxies) && $this->cloudflare_ip_address_in_range($ip)) {
        $trustedProxies[] = $ip;
      }
    }
    $event->getRequest()->setTrustedProxies($trustedProxies);
  }


  function cloudflare_ip_address_in_range($checkip, $range = FALSE) {
    if (!$range) {
      $range = $this->cloudflare_ip_ranges();
    }
    if (is_array($range)) {
      foreach ($range as $ip_range) {
        if ($this->cloudflare_ip_address_in_range($checkip, $ip_range)) {
          return TRUE;
        }
      }
      return FALSE;
    }

    @list($ip, $len) = explode('/', $range);

    if (($min = ip2long($ip)) !== FALSE && !is_null($len)) {
      $clong = ip2long($checkip);
      $max = ($min | (1 << (32 - $len)) - 1);
      if ($clong > $min && $clong < $max) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Get a list of cloudflare IP Ranges
   */
  function cloudflare_ip_ranges() {
    if ($cache = \Drupal::cache()->get('cloudflare_ip_ranges')) {
      return $cache->data;
    }
    else {
      $ip_blocks = file_get_contents("https://www.cloudflare.com/ips-v4");
      $cloudflare_ips = explode("\n", $ip_blocks);
      $cloudflare_ips = array_map('trim', $cloudflare_ips);

      \Drupal::cache()
        ->set('cloudflare_ip_ranges', $cloudflare_ips, Cache::PERMANENT);
      return $cloudflare_ips;
    }
  }

}

?>
