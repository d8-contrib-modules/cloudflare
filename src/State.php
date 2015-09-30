<?php

/**
 * @file
 * Contains \Drupal\cloudflare\State.
 */

namespace Drupal\cloudflare;

use Drupal\Core\State\StateInterface;
use DateTime;

/**
 * Tracks rate limits associated with CloudFlare Api.
 */
class State implements CloudFlareStateInterface {
  const TAG_PURGE_DAILY_COUNT = "cloudflare_tag_purge_daily_count";
  const API_RATE_COUNT = "cloudflare_api_rate_count";

  const TAG_PURGE_DAILY_COUNT_START = "cloudflare_tag_purge_daily_count";
  const API_RATE_COUNT_START = "cloudflare_api_rate_count";

  /**
   * State constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementTagPurgeDailyCount() {
    $count = $this->state->get(self::TAG_PURGE_DAILY_COUNT);
    $last_recorded_timestamp = $this->state->get(self::TAG_PURGE_DAILY_COUNT_START);
    $last_recorded_timestamp = is_null($last_recorded_timestamp) ? new DateTime('2001-01-01') : $last_recorded_timestamp;

    $now = new DateTime();
    $todays_date = $now->format('Y-m-d');
    $last_recorded_date = $last_recorded_timestamp->format('Y-m-d');

    if (empty($last_recorded_timestamp) || $last_recorded_date != $todays_date) {
      $this->state->set(self::TAG_PURGE_DAILY_COUNT, 1);
      $this->state->set(self::TAG_PURGE_DAILY_COUNT_START, $now);
    }

    else {
      $this->state->set(self::TAG_PURGE_DAILY_COUNT, ++$count);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTagDailyCount() {
    return $this->state->get(self::TAG_PURGE_DAILY_COUNT);
  }

  /**
   * {@inheritdoc}
   */
  public function incrementApiRateCount() {
    $count = $this->state->get(self::API_RATE_COUNT_START);
    $last_recorded_timestamp = $this->state->get(self::API_RATE_COUNT_START);
    $last_recorded_timestamp = is_null($last_recorded_timestamp) ? new DateTime('2001-01-01') : $last_recorded_timestamp;

    $now = new DateTime();
    $interval = $now->diff($last_recorded_timestamp);
    $minutes_passed = $interval->format('%i');

    if (empty($last_recorded_timestamp) || $minutes_passed > 5) {
      $this->state->set(self::API_RATE_COUNT_START, 1);
      $this->state->set(self::API_RATE_COUNT_START, $now);
    }

    else {
      $this->state->set(self::API_RATE_COUNT_START, ++$count);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiRateCount() {
    return $this->state->get(self::API_RATE_COUNT_START);
  }

}
