<?php
/**
 * @file
 * Contains \Drupal\cloudflarepurger\Tests\DiagnosticCheck\DailyTagPurgeLimitCheckTest.
 */

namespace Drupal\cloudflarepurger\Tests\DiagnosticCheck;
use \DateTime;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\cloudflare\State;
use Drupal\cloudflarepurger\DiagnosticCheckTestBase;
use Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\DailyTagPurgeLimitCheck;

/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group cloudflare
 */
class DailyTagPurgeLimitCheckTest extends DiagnosticCheckTestBase {
  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests that DailyTagPurgeLimitCheck responds as expected.
   *
   * @param int $api_rate
   *   The currentAPI rate to test.
   * @param int $expected_severity
   *   The expected diagnostic severity.
   *
   * @dataProvider dailyTagPurgeLimitCheckProvider
   */
  public function testDailyTagPurgeLimitCheck($api_rate, $expected_severity) {
    $this->drupalState->set(State::TAG_PURGE_DAILY_COUNT, $api_rate);
    $this->drupalState->set(State::TAG_PURGE_DAILY_COUNT_START, new DateTime());

    $api_rate_limit_check = new DailyTagPurgeLimitCheck([], '23123', 'this is a definition', $this->cloudflareState, $this->composerDependencyStub);
    $actual_severity = $api_rate_limit_check->run();
    $this->assertEquals($expected_severity, $actual_severity);
  }

  /**
   * Data provider for validating DailyTagPurgeLimitCheck.
   *
   * @return array[]
   *   Returns per data set an array with:
   *     - count of daily tag purge requests
   *     - expected status returned by diagnostic check
   */
  public function dailyTagPurgeLimitCheckProvider() {
    return [
      [NULL, DiagnosticCheckInterface::SEVERITY_OK],
      [0, DiagnosticCheckInterface::SEVERITY_OK],
      [1, DiagnosticCheckInterface::SEVERITY_OK],
      [149, DiagnosticCheckInterface::SEVERITY_OK],
      [150, DiagnosticCheckInterface::SEVERITY_WARNING],
      [151, DiagnosticCheckInterface::SEVERITY_WARNING],
      [152, DiagnosticCheckInterface::SEVERITY_WARNING],
      [199, DiagnosticCheckInterface::SEVERITY_WARNING],
      [200, DiagnosticCheckInterface::SEVERITY_ERROR],
      [201, DiagnosticCheckInterface::SEVERITY_ERROR],
      [220, DiagnosticCheckInterface::SEVERITY_ERROR],
    ];
  }

}
