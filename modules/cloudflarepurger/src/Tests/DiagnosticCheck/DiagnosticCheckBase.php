<?php
/**
 * @file
 * Contains \Drupal\cloudflarepurger\Tests\DiagnosticCheck\DiagnosticCheckBase.
 */

namespace Drupal\cloudflarepurger\Tests\DiagnosticCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\cloudflare\State;
use Drupal\Core\DependencyInjection\ContainerBuilder;
/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group cloudflare
 */
abstract class DiagnosticCheckBase extends UnitTestCase {
  protected $container;
  protected $drupalState;
  protected $cloudflareState;
  protected $timestampStub;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalState = new \Drupal\Core\State\State(new KeyValueMemoryFactory());
    $this->timestampStub = $this->getMockBuilder('Drupal\cloudflare\Timestamp')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cloudflareState = new State($this->drupalState, $this->timestampStub);

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);
  }

}
