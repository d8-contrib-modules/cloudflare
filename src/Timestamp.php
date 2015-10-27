<?php
/**
 * @file
 * Provides an injectable service for a Timestamp.
 */

namespace Drupal\cloudflare;

use \DateTime;

/**
 * Timestamp class to get datetime.
 */
class Timestamp implements CloudFlareTimestampInterface {

  /**
   * {@inheritdoc}
   */
  public function now() {
    return new DateTime();
  }

}
