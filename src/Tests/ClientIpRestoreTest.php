<?php

/**
 * @file
 * Definition of Drupal\cloudflare\Tests.
 */

namespace Drupal\cloudflare\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\cloudflare\EventSubscriber\ClientIpRestore;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use DateTime;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests functionality of CloudFlareState object.
 *
 * @group cloudflare
 * @covers Drupal\cloudflare\EventSubscriber\ClientIpRestore
 */
class ClientIpRestoreTest extends UnitTestCase {
  use StringTranslationTrait;

  protected $container;

  public function setUp() {
    parent::setUp();
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $fast_cache = new MemoryBackend('foo');

    \Drupal::setContainer($this->container);
  }

  /**
   * @param $client_ip_restore_enabled
   * @param $cf_header
   * @param $remote_header_ip
   * @param $expected_message
   *
   * @dataProvider requestProvider
   */
  public function testEnabledClientIpRestoreProvider($client_ip_restore_enabled, $remote_header_ip, $cf_header, $expected_message) {
    syslog(1,"testEnabledClientIpRestoreProvider");
    $logger = $this->getMock(LoggerInterface::class);

    if (empty($expected_message)) {
      $logger->expects($this->never())
        ->method('warning')
        ->with($expected_message);
    }
    else {
      $logger->expects($this->once())
        ->method('warning')
        ->with($expected_message);
    }

    $config_factory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->atLeastOnce())
      ->method('get')
      ->with(ClientIpRestore::CLOUDFLARE_CLIENT_IP_RESTORE_ENABLED)
      ->will($this->returnValue($client_ip_restore_enabled));
    $config_factory->expects($this->once())
      ->method('get')
      ->will($this->returnValue($config));

    $cache_backend = new MemoryBackend('foo');

    $client_ip_restore = new ClientIpRestore(
      $config_factory,
      $cache_backend,
      $this->getMock(ClientInterface::class),
      $logger
    );

    $request = Request::create('/test', 'get');
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $event = new GetResponseEvent($kernel, $request, 'foo', new NotFoundHttpException('foo'));
    if (!empty($cf_header)) {
      $request->headers->set('HTTP_CF_CONNECTING_IP', $cf_header);
    }

    if (!empty($header_ip)) {
      $request->headers->set('REMOTE_ADDR', $remote_header_ip);
    }

    try{
      $client_ip_restore->onRequest($event);
    }
    catch (\Exception $e){
      //syslog(1, $e);
    }
  }

  //$client_ip_restore_enabled, $cf_header, $remote_header_ip, $expected_message
  public function requestProvider() {
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($this->container);


    $message1 = $this->t("REMOTE_ADDR does not match a known CloudFlare IP and there is HTTP_CF_CONNECTING_IP.  Someone is attempting to mask their IP address by setting HTTP_CF_CONNECTING_IP.");
    return [
      [FALSE, '192.168.2.201', NULL, ''],
      [TRUE, '192.168.2.201', '', $this->t('Request came through without being routed through CloudFlare.')],
      [TRUE, '192.168.2.201', '103.21.244.0', $message1],
      [TRUE, '192.168.2.201', '103.21.244.0', "BLAH"],
    ];
  }

}
