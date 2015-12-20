<?php

/**
 * @file
 * Definition of \Drupal\cloudflare\Tests\CloudFlareAdminSettingsFormTest.
 */

namespace Drupal\cloudflare\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\CloudFlareAdminSettingsForm.
 *
 * @group cloudflare
 */
class CloudFlareAdminSettingsFormTest extends WebTestBase {
  public static $modules = ['cloudflare'];

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
  }

  /**
   * Tests that form has critical fields as expected.
   */
  public function testConfigFormDisplay() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertText('This will help suppress watchdog warnings regarding requests bypassing CloudFlare', 'Helper Text');
    $this->assertField('apikey', 'Make sure that the Api Key field is visible..');
    $this->assertField('email', 'Make sure the edit email field is visible.');
    $this->assertField('client_ip_restore_enabled', 'Make sure the Restore Client Ip Address checkbox is visible.');
    $this->assertField('bypass_host', 'Make sure the bypass host field is visible.');
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess() {
    $this->drupalGet($this->route);
    $this->assertResponse(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->route);
    $this->assertResponse(200);
  }

  /**
   * Test posting an invalid host with https protocol to the form.
   */
  public function testInvalidBypassHostWithHttps() {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'apikey' => 'blah',
      'email' => 'test@test.com',
      'client_ip_restore_enabled' => TRUE,
      'bypass_host' => 'https://blah.com',
    ];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));
    $this->assertText('Please enter a host without http/https');
  }

  /**
   * Test posting an invalid host with http protocol to the form.
   */
  public function testInvalidBypassHostWithHttp() {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'apikey' => 'blah',
      'email' => 'test@test.com',
      'client_ip_restore_enabled' => TRUE,
      'bypass_host' => 'http://blah.com',
    ];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));
    $this->assertText('Please enter a host without http/https');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidBypassHost() {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'apikey' => 'blah',
      'email' => 'test@test.com',
      'client_ip_restore_enabled' => TRUE,
      'bypass_host' => 'blah!@#!@',
    ];
    $this->drupalPostForm($this->route, $edit, t('Save configuration'));
    $this->assertText('You have entered an invalid host.');
  }

  /*
   * Tests that valid credentials are accepted.
   *
   * @todo need to figure out how to mock the HTTP response from CloudFlare.
   */

}
