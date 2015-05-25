<?php
namespace Drupal\cloudflare;

use Drupal\Component\Serialization;


class CloudFlareApiResponse {

  private $success;
  private $errorCode;
  private $errorMessage;
  private $messages;
  private $id;


  /**
   * @return mixed
   */
  public function getSuccess() {
    return $this->success;
  }

  /**
   * @return mixed
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
   * Parses common fields for a json response from CloudFlare
   *
   * @param $json_response
   */
  public function __constructor($json_response) {
    $response = \Drupal\Component\Serialization\Json::decode($json_response);

    $this->success = $response['success'];
    $this->errorCode = $response['errors']['code'];
    $this->errorMessage = $response['errors']['message'];
    $this->messages = $response['messages'];
    $this->id = $response['id'];
  }
}