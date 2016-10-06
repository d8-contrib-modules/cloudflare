<?php

namespace Drupal\cloudflare;

/**
 * Tests that Composer dependencies for CloudFlare are met.
 */
interface CloudFlareComposerDependenciesCheckInterface {
  const ERROR_MESSAGE = "Missing Composer dependencies for CloudFlare. <br /> From the root of your site install composer dependencies by running `composer require d8-contrib-modules/cloudflarephpsdk \"1.0.0-alpha3\"`";

  /**
   * Tests that composer dependencies for CloudFlare are met.
   *
   * @return bool
   *   TRUE if composer dependencies are met. FALSE otherwise.
   */
  public function check();

  /**
   * Asserts that composer dependencies for CloudFlare are met.
   *
   * @throws \Drupal\cloudflare\Exception\ComposerDependencyException
   *   Exception thrown if composer dependencies are met.
   */
  public function assert();

}
