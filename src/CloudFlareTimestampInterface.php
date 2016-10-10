<?php

namespace Drupal\cloudflare;

/**
 * Provides an injectable facility for getting the current time.
 */
interface CloudFlareTimestampInterface {

  /**
   * Gets the current DateTime.
   *
   * @return \DateTime
   *   DateTime representing the current time.
   */
  public function now();

}
