<?php

namespace Drupal\cloudflare_form_tester\Mocks;

use Psr\Log\LoggerInterface;
use Drupal\cloudflare\Exception\ComposerDependencyException;
use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;

/**
 * Tests that composer dependencies are met.
 */
class ComposerDependenciesCheckMock implements CloudFlareComposerDependenciesCheckInterface {

  /**
   * Set the first time that this function is called in a bootstrap.
   *
   * @var bool
   */
  public static $areDependenciesMet = NULL;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(LoggerInterface $logger) {
    return new static(
      $logger
    );
  }

  /**
   * ComposerDependenciesCheck constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    $are_dependencies_met = \Drupal::state()->get('cloudflaretesting.assertComposerDependenciesMet');

    if (!$are_dependencies_met) {
      $this->logger->critical(self::ERROR_MESSAGE);
    }

    return $are_dependencies_met;
  }

  /**
   * {@inheritdoc}
   */
  public function assert() {
    if (!$this->check()) {
      throw new ComposerDependencyException(self::ERROR_MESSAGE);
    }
  }

  /**
   * Tells the mock to assert if dependencies are met or not.
   *
   * @param bool $are_dependencies_met
   *   TRUE to mock dependencies are met.  FALSE otherwise.
   */
  public static function mockComposerDependenciesMet($are_dependencies_met) {
    \Drupal::state()->set('cloudflaretesting.assertComposerDependenciesMet', $are_dependencies_met);
  }

}
