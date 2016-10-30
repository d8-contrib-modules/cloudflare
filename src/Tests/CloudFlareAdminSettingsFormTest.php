<?php

namespace Drupal\cloudflare\Tests;

use Drupal\cloudflare_form_tester\Mocks\ComposerDependenciesCheckMock;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\cloudflare_form_tester\Mocks\ZoneMock;

/**
 * Tests \Drupal\purge_ui\Form\CloudFlareAdminSettingsForm.
 *
 * @group cloudflare
 */
class CloudFlareAdminSettingsFormTest extends WebTestBase {
  public static $modules = ['cloudflare', 'cloudflare_form_tester', 'ctools'];

  /**
   * An admin user that has been setup for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Route providing the main configuration form of the cloudflare module.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'cloudflare.admin_settings_form';

  /**
   * Setup the test.
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['access administration pages']);
    $this->route = Url::fromRoute('cloudflare.admin_settings_form');
    $this->drupalLogin($this->adminUser);
    ZoneMock::mockAssertValidCredentials(TRUE);
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testValidCredentials() {
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
    ];
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertUrl('/admin/config/services/cloudflare/two?js=nojs');
    $this->drupalPostForm(NULL, [], t('Finish'));
    $this->assertRaw('68ow48650j63zfzx1w9jd29cr367u0ezb6a4g');
    $this->assertRaw('test@test.com');
    $this->assertRaw('testdomain.com');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testMultiZoneSelection() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
    ];
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
    ZoneMock::mockMultiZoneAccount(TRUE);
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertUrl('/admin/config/services/cloudflare/two?js=nojs');
    $this->drupalPostForm(NULL, ['zone_selection' => "123456789999"], t('Finish'));
    $this->assertRaw('68ow48650j63zfzx1w9jd29cr367u0ezb6a4g');
    $this->assertRaw('testdomain2.com');
  }

  /**
   * Test posting an invalid host with https protocol to the form.
   */
  public function testInvalidBypassHostWithHttps() {
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
      'client_ip_restore_enabled' => TRUE,
      'bypass_host' => 'https://blah.com',
    ];
    ZoneMock::mockAssertValidCredentials(TRUE);
    $container = \Drupal::getContainer();
    $config_factory = $container->get('config.factory');
    $logger_channel_cloudflare = $container->get('logger.channel.cloudflare');
    $cloudflare_state = $container->get('cloudflare.state');
    $composer_dependencies_check = $container->get('cloudflare.composer_dependency_check');

    $zone_mock = new ZoneMock($config_factory, $logger_channel_cloudflare, $cloudflare_state, $composer_dependencies_check);
    $container->set('cloudflare.zone', $zone_mock);

    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertText('Please enter a host without http/https');
  }

  /**
   * Test posting an invalid host with http protocol to the form.
   */
  public function testInvalidBypassHostWithHttp() {
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
      'client_ip_restore_enabled' => TRUE,
      'bypass_host' => 'http://blah.com',
    ];
    ZoneMock::mockAssertValidCredentials(TRUE);
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertText('Please enter a host without http/https');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidBypassHost() {
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
      'client_ip_restore_enabled' => TRUE,
      'bypass_host' => 'blah!@#!@',
    ];
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertText('You have entered an invalid host.');
  }

}
