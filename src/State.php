<?php

namespace Drupal\cloudflare;

use Drupal\Core\State\StateInterface;
use DateTime;

/**
 * Tracks rate limits associated with CloudFlare Api.
 */
class State implements CloudFlareStateInterface {
  const TAG_PURGE_DAILY_COUNT = "cloudflare_tag_purge_daily_count";
  const TAG_PURGE_DAILY_COUNT_START = "cloudflare_tag_purge_daily_start";

  const API_RATE_COUNT = "cloudflare_api_rate_count";
  const API_RATE_COUNT_START = "cloudflare_api_rate_count_start";

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * Timestamp service.
   *
   * @var \Drupal\CloudFlare\CloudFlareTimestampInterface
   */
  protected $timestamper;

  /**
   * State constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state service.
   * @param \Drupal\CloudFlare\CloudFlareTimestampInterface $timestamper
   *   Cloudflare timestamp service.
   */
  public function __construct(StateInterface $state, CloudFlareTimestampInterface $timestamper) {
    $this->state = $state;
    $this->timestamper = $timestamper;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementTagPurgeDailyCount() {
    $count = $this->state->get(self::TAG_PURGE_DAILY_COUNT);
    $last_recorded_timestamp = $this->state->get(self::TAG_PURGE_DAILY_COUNT_START);
    $last_recorded_timestamp = is_null($last_recorded_timestamp) ? new DateTime('2001-01-01') : $last_recorded_timestamp;

    $now = $this->timestamper->now();
    $todays_date = $now->format('Y-m-d');
    $last_recorded_date = $last_recorded_timestamp->format('Y-m-d');

    if (empty($last_recorded_timestamp) || ($last_recorded_date != $todays_date)) {
      $this->state->set(self::TAG_PURGE_DAILY_COUNT, 1);
      $this->state->set(self::TAG_PURGE_DAILY_COUNT_START, $now);
    }

    else {
      $count++;
      $this->state->set(self::TAG_PURGE_DAILY_COUNT, $count);
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
    $count = $this->state->get(self::API_RATE_COUNT);
    $last_recorded_timestamp = $this->state->get(self::API_RATE_COUNT_START);
    $last_recorded_timestamp = is_null($last_recorded_timestamp) ? new DateTime('2001-01-01') : $last_recorded_timestamp;

    $now = $this->timestamper->now();
    $diff = $now->getTimestamp() - $last_recorded_timestamp->getTimestamp();
    $minutes_passed = $diff / 60;

    if ($minutes_passed >= 5) {
      $this->state->set(self::API_RATE_COUNT, 1);
      $this->state->set(self::API_RATE_COUNT_START, $now);
    }

    else {
      $this->state->set(self::API_RATE_COUNT, ++$count);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiRateCount() {
    $count = $this->state->get(self::API_RATE_COUNT);
    return $count;
  }

}
