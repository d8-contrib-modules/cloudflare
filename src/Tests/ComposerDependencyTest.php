<?php

namespace Drupal\cloudflare\Tests;

use Drupal\cloudflare_form_tester\Mocks\ComposerDependenciesCheckMock;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ComposerDependencyTest.
 *
 * @group cloudflare
 */
class ComposerDependencyTest extends WebTestBase {
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
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testDependenciesUnmet() {
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(FALSE);
    $this->drupalGet($this->route);
    $this->assertRaw('Missing Composer dependencies for CloudFlare');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testDependenciesMet() {
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
    $this->drupalGet($this->route);
    $this->assertNoRaw('Missing Composer dependencies for CloudFlare');
  }

}
