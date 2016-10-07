<?php

namespace Drupal\cloudflare;

use Psr\Log\LoggerInterface;
use Drupal\cloudflare\Exception\ComposerDependencyException;

/**
 * Tests that composer dependencies are met.
 */
class ComposerDependenciesCheck implements CloudFlareComposerDependenciesCheckInterface {

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
    // This check will help prevent multiple redundant criticals from being
    // logged.
    if (!is_null(self::$areDependenciesMet)) {
      return self::$areDependenciesMet;
    }

    if (class_exists('\CloudFlarePhpSdk\ApiEndpoints\ZoneApi')) {
      self::$areDependenciesMet = TRUE;
    }

    else {
      self::$areDependenciesMet = FALSE;
      $this->logger->critical(self::ERROR_MESSAGE);
    }

    return self::$areDependenciesMet;
  }

  /**
   * {@inheritdoc}
   */
  public function assert() {
    if (!$this->check()) {
      throw new ComposerDependencyException(self::ERROR_MESSAGE);
    }
  }

}
