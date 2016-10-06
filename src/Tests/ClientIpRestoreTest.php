<?php

namespace Drupal\cloudflare\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\cloudflare\EventSubscriber\ClientIpRestore;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests functionality of CloudFlareState object.
 *
 * @group cloudflare
 *
 * @covers \Drupal\cloudflare\EventSubscriber\ClientIpRestffore
 */
class ClientIpRestoreTest extends UnitTestCase {
  use StringTranslationTrait;

  protected $container;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('getPathFromRoute')
      ->with('cloudflare.admin_settings_form', [])
      ->willReturn('/admin/config/services/cloudflare');
    $this->container->set('url_generator', $this->urlGenerator);

    \Drupal::setContainer($this->container);
  }

  /**
   * Test ClientIpRestoreProvider functionality.
   *
   * @param bool $client_ip_restore_enabled
   *   Bool to indicate if client ip restore is enabled.
   * @param string $remote_header_ip
   *   The server server ip.
   * @param string $cf_header
   *   CloudFlare header with the originating user's IP.
   * @param string $bypass_host
   *   Host/Domain that is expected to bypass CloudFlare.
   * @param string $expected_message
   *   The expected message to be logged.
   * @param string $expected_client_ip
   *   The expected clientIp address after ClientIpRestore has been run.
   *
   * @dataProvider requestProvider
   */
  public function testEnabledClientIpRestoreProvider($client_ip_restore_enabled, $host_header, $remote_header_ip, $cf_header, $bypass_host, $expected_message, $expected_client_ip) {
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

    // Create a map of arguments to return values.
    $map = array(
      array(ClientIpRestore::CLOUDFLARE_BYPASS_HOST, $bypass_host),
      array(ClientIpRestore::CLOUDFLARE_CLIENT_IP_RESTORE_ENABLED, $client_ip_restore_enabled),
    );
    $config->expects($this->atLeastOnce())
      ->method('get')
      ->will($this->returnValueMap($map));

    $config_factory->expects($this->once())
      ->method('get')
      ->will($this->returnValue($config));

    $cf_ips = explode("\n",
      "103.21.244.0/22
      103.22.200.0/22
      103.31.4.0/22
      104.16.0.0/12
      108.162.192.0/18
      141.101.64.0/18
      162.158.0.0/15
      172.64.0.0/13
      173.245.48.0/20
      188.114.96.0/20
      190.93.240.0/20
      197.234.240.0/22
      198.41.128.0/17
      199.27.128.0/21"
    );
    $cf_ips = array_map('trim', $cf_ips);

    $cache_backend = new MemoryBackend('foo');
    $cache_backend->set(ClientIpRestore::CLOUDFLARE_RANGE_KEY, $cf_ips);

    $client_ip_restore = new ClientIpRestore(
      $config_factory,
      $cache_backend,
      $this->getMock(ClientInterface::class),
      $logger
    );

    $request = Request::create('/test', 'get');
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

    if (!empty($cf_header)) {
      $request->server->set('HTTP_CF_CONNECTING_IP', $cf_header);
    }

    if (!empty($host_header)) {
      $request->headers->set('host', $host_header);
    }

    if (!empty($remote_header_ip)) {
      $request->server->set('REMOTE_ADDR', $remote_header_ip);
    }

    $request->overrideGlobals();
    $event = new GetResponseEvent($kernel, $request, 'foo', new NotFoundHttpException('foo'));
    $client_ip_restore->onRequest($event);
    $this->assertEquals($expected_client_ip, $request->getClientIp());

  }

  /**
   * Provider for testing ClientIpRestoreProvider.
   *
   * @return array
   *   Test Data to simulate incoming request and the expected results..
   */
  public function requestProvider() {
    // The setup container is not yet available.
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);

    $message0 = $this->t('Request came through without being routed through CloudFlare.');
    $message1 = $this->t("Client IP of 192.168.2.203 does not match a known CloudFlare IP but there is HTTP_CF_CONNECTING_IP of 103.21.244.0.");
    $message2 = $this->t('Request has already been updated.  This functionality should be deactivated. Please go <a href="@link_to_settings">here</a> to disable "Restore Client Ip Address".', ['@link_to_settings' => "/admin/config/services/cloudflare"]);

    $test0 = [
      FALSE,
      'cftest.dev',
      '192.168.2.201',
      NULL,
      'edit.cftest.dev',
      '',
      '192.168.2.201',
    ];

    $test1 = [
      TRUE,
      'cftest.dev',
      '192.168.2.202',
      '',
      'edit.cftest.dev',
      $message0,
      '192.168.2.202',
    ];

    $test2 = [
      TRUE,
      'cftest.dev',
      '192.168.2.203',
      '103.21.244.0',
      'edit.cftest.dev',
      $message1,
      '192.168.2.203',
    ];

    $test3 = [
      TRUE,
      'cftest.dev',
      '103.21.244.0',
      '103.21.244.0',
      'edit.cftest.dev',
      $message2,
      '103.21.244.0',
    ];

    $test4 = [
      TRUE,
      'cftest.dev',
      '103.21.244.0',
      '5.5.5.5',
      'edit.cftest.dev',
      '',
      '5.5.5.5',
    ];

    $test5 = [
      FALSE,
      'edit.cftest.dev',
      '192.168.2.201',
      NULL,
      'edit.cftest.dev',
      '',
      '192.168.2.201',
    ];

    $test6 = [
      TRUE,
      'edit.cftest.dev',
      '192.168.2.202',
      '',
      'edit.cftest.dev',
      '',
      '192.168.2.202',
    ];

    $test7 = [
      TRUE,
      'edit.cftest.dev',
      '192.168.2.203',
      '103.21.244.0',
      'edit.cftest.dev',
      '',
      '192.168.2.203',
    ];

    $test8 = [
      TRUE,
      'edit.cftest.dev',
      '103.21.244.0',
      '103.21.244.0',
      'edit.cftest.dev',
      '',
      '103.21.244.0',
    ];

    $test9 = [
      TRUE,
      'edit.cftest.dev',
      '103.21.244.0',
      '5.5.5.5',
      'edit.cftest.dev',
      '',
      '103.21.244.0',
    ];

    $test10 = [
      FALSE,
      '',
      '192.168.2.201',
      NULL,
      '',
      '',
      '192.168.2.201',
    ];

    $test11 = [
      TRUE,
      '',
      '192.168.2.202',
      '',
      '',
      $message0,
      '192.168.2.202',
    ];
    $test12 = [
      TRUE,
      '',
      '192.168.2.203',
      '103.21.244.0',
      '',
      $message1,
      '192.168.2.203',
    ];
    $test13 = [
      TRUE,
      '',
      '103.21.244.0',
      '103.21.244.0',
      '',
      $message2,
      '103.21.244.0',
    ];

    $test14 = [
      TRUE,
      '',
      '103.21.244.0',
      '5.5.5.5',
      '',
      '',
      '5.5.5.5',
    ];

    return [
      $test0,
      $test1,
      $test2,
      $test3,
      $test4,
      $test5,
      $test6,
      $test7,
      $test8,
      $test9,
      $test10,
      $test11,
      $test12,
      $test13,
      $test14,
    ];
  }

}
