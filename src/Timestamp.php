<?php

namespace Drupal\cloudflare;

use \DateTime;

/**
 * Timestamp class to get datetime.
 *
 * @todo find a better approach. This was a hack to unblock automated testing.
 */
class Timestamp implements CloudFlareTimestampInterface {

  /**
   * {@inheritdoc}
   */
  public function now() {
    return new DateTime();
  }

}
