<?php

namespace Drupal\cloudflare\Tests;

use Drupal\cloudflare_form_tester\Mocks\ComposerDependenciesCheckMock;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\cloudflare_form_tester\Mocks\ZoneMock;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

/**
 * Tests \Drupal\purge_ui\Form\CloudFlareAdminSettingsForm.
 *
 * @group cloudflare
 */
class CloudFlareAdminSettingsInvalidFormTest extends WebTestBase {
  public static $modules = ['cloudflare', 'ctools'];

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
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
  }

  /**
   * Tests that form has critical fields as expected.
   */
  public function testConfigFormDisplay() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertText('This will help suppress log warnings regarding requests bypassing CloudFlare', 'Helper Text');
    $this->assertField('apikey', 'Make sure that the Api Key field is visible..');
    $this->assertField('email', 'Make sure the edit email field is visible.');
    $this->assertField('client_ip_restore_enabled', 'Make sure the Restore Client Ip Address checkbox is visible.');
    $this->assertField('bypass_host', 'Make sure the bypass host field is visible.');
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess() {
    // @todo troubleshoot why testing the route as an anonymous user
    // throws a 500 code for travis CI.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertResponse(200);
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidCredentials() {
    $mock = new MockHandler([
      new Response(403, [], "This could be a problem."),
    ]);

    $container = \Drupal::getContainer();
    $config_factory = $container->get('config.factory');
    $logger_channel_cloudflare = $container->get('logger.channel.cloudflare');
    $cloudflare_state = $container->get('cloudflare.state');
    $composer_dependencies_check = $container->get('cloudflare.composer_dependency_check');

    $zone_mock = new ZoneMock($config_factory, $logger_channel_cloudflare, $cloudflare_state, $composer_dependencies_check);
    ZoneMock::mockAssertValidCredentials(FALSE);
    $container->set('cloudflare.zone', $zone_mock);

    $this->drupalLogin($this->adminUser);
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
    ];
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertUrl('/admin/config/services/cloudflare');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testUpperCaseInvalidCredentials() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
    $edit = [
      'apikey' => 'fDK5M9sf51x6CEAspHSUYM4vt40m5XC2T6i1K',
      'email' => 'test@test.com',
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertText('Invalid Api Key: Key can only contain lowercase or numerical characters.');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidKeyLength() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
    $edit = [
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g0',
      'email' => 'test@test.com',
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertText('Invalid Api Key: Key should be 37 chars long.');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidKeySpecialChars() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(FALSE);
    $edit = [
      'apikey' => '!8ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm($this->route, $edit, t('Next'));
    $this->assertText('Invalid Api Key: Key can only contain alphanumeric characters.');
  }

}
