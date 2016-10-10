<?php

namespace Drupal\cloudflarepurger\Tests\DiagnosticCheck;

use \DateTime;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\cloudflare\State;
use Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\ApiRateLimitCheck;
use Drupal\cloudflarepurger\DiagnosticCheckTestBase;

/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group cloudflare
 *
 * @covers \Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\ApiRateLimitCheck
 */
class ApiRateLimitCheckTest extends DiagnosticCheckTestBase {

  /**
   * Tests that ApiRateLimitCheck Responds as expected with test purge rates.
   *
   * @param int $api_rate
   *   The currentAPI rate to test.
   * @param int $expected_severity
   *   The expected diagnostic severity.
   *
   * @dataProvider apiRateLimitProvider
   */
  public function testApiRateLimitCheck($api_rate, $expected_severity) {
    $this->drupalState->set(State::API_RATE_COUNT, $api_rate);
    $this->drupalState->set(State::API_RATE_COUNT_START, new DateTime());

    $api_rate_limit_check = new ApiRateLimitCheck([], '23123', 'this is a definition', $this->cloudflareState, $this->composerDependencyStub);
    $actual_severity = $api_rate_limit_check->run();
    $this->assertEquals($expected_severity, $actual_severity);
  }

  /**
   * Data provider for validating ApiRateLimitCheck.
   *
   * @return array[]
   *   Returns per data set an array with:
   *     - count of daily tag purge requests
   *     - expected status returned by diagnostic check
   */
  public function apiRateLimitProvider() {
    return [
      [NULL, DiagnosticCheckInterface::SEVERITY_OK],
      [0, DiagnosticCheckInterface::SEVERITY_OK],
      [1, DiagnosticCheckInterface::SEVERITY_OK],
      [500, DiagnosticCheckInterface::SEVERITY_OK],
      [899, DiagnosticCheckInterface::SEVERITY_OK],
      [900, DiagnosticCheckInterface::SEVERITY_WARNING],
      [901, DiagnosticCheckInterface::SEVERITY_WARNING],
      [1199, DiagnosticCheckInterface::SEVERITY_WARNING],
      [1200, DiagnosticCheckInterface::SEVERITY_ERROR],
      [1220, DiagnosticCheckInterface::SEVERITY_ERROR],
    ];
  }

}
