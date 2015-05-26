<?php
/**
 * @file
 * Contains CloudFlareApiException.
 */

namespace Drupal\cloudflare\CloudFlarePhpSdk;

/**
 * Defines CloudFlareApiException.
 *
 * The purpose of this class is to translate exceptions from API and Guzzle
 * (the tool being used for webservices) to the application layer so that they
 * can be handled accordingly.
 *
 */
class CloudFlareApiException extends \Exception {


  /**
   * API level error code.
   *
   * NOTE: This is NOT the HTTP response code.
   *
   * @var null|string
   */
  private $apiResponseCode;

  /**
   * HTTP response code.
   *
   * @var null|string
   */
  private $httpResponseCode;

  /**
   * Error Message returned from API.
   *
   * @var null|string
   */
  //protected $message;


  public function __construct($httpResponseCode, $apiResponseCode, $message, Exception $previous = NULL) {
    parent::__construct($message, $apiResponseCode, $previous);
    $this -> httpResponseCode = $httpResponseCode;
    $this -> apiResponseCode = $apiResponseCode;
    $this -> message = $message;
  }
}