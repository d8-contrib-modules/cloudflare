<?php

namespace Drupal\cloudflarepurger\Tests\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\cloudflarepurger\DiagnosticCheckTestBase;
use Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\CredentialCheck;

/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group cloudflare
 */
class CredentialTestCheckTest extends DiagnosticCheckTestBase {

  /**
   * Tests that CredentialTestCheck Responds as expected with test purge rates.
   *
   * @param int $cred_status
   *   The current API rate to test.
   * @param int $expected_severity
   *   The expected diagnostic severity.
   *
   * @dataProvider credentialCheckProvider
   *
   * @covers \Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\CredentialCheck
   */
  public function testCredentialTestCheck($cred_status, $expected_severity) {
    $config_factory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->atLeastOnce())
      ->method('get')
      ->with('valid_credentials')
      ->will($this->returnValue($cred_status));
    $config_factory->expects($this->once())
      ->method('get')
      ->will($this->returnValue($config));

    $credential_check = new CredentialCheck([], '23123', 'this is a definition', $config_factory);
    $actual_severity = $credential_check->run();
    $this->assertEquals($expected_severity, $actual_severity);
  }

  /**
   * PhpUnit provider to api rate limits.
   */
  public function credentialCheckProvider() {
    return [
      [NULL, DiagnosticCheckInterface::SEVERITY_ERROR],
      [TRUE, DiagnosticCheckInterface::SEVERITY_OK],
      [FALSE, DiagnosticCheckInterface::SEVERITY_ERROR],
      [0, DiagnosticCheckInterface::SEVERITY_ERROR],
      [1, DiagnosticCheckInterface::SEVERITY_OK],
    ];
  }

}
