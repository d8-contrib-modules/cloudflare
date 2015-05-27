<?php

/**
 * @file
 * Implementation of CloudFlareApiResponse class.
 */

namespace Drupal\cloudflare\CloudFlarePhpSdk;

use Drupal\Component\Serialization;
/**
 * Contains response information from the API.
 */
class CloudFlareApiResponse {

  /** @var bool The status code of the response */
  private $success;

  /** @var string The status code of the response */
  private $errorCode;
  private $errorMessage;
  private $messages;
  private $id;


  /**
   * Returns if the response was successful or not.
   * @return boolean
   *  TRUE if successful, FALSE otherwise.
   */
  public function getSuccess() {
    return $this->success;
  }

  /**
   * Returns the
   * @return string
   *
   */
  public function getErrorCode() {
    return $this->errorCode;
  }

  /**
   * @return mixed
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * @return mixed
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Parses common fields for a json response from CloudFlare.
   *
   * @param $json_response
   */
  public function __construct($json_response) {
    $response = \Drupal\Component\Serialization\Json::decode($json_response);

    $this->success = $response['success'];
    $this->errorCode = $response['errors']['code'];
    $this->errorMessage = $response['errors']['message'];
    $this->messages = $response['messages'];
    $this->id = $response['id'];
  }
}
