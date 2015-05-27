<?php

/**
 * @file
 * Base functionality for sending requests to the CloudFlare API.
 */

namespace Drupal\cloudflare\CloudFlarePhpSdk;

use Drupal\cloudflare\ApiTypes;
use GuzzleHttp;

/**
 * Base functionality for interacting with CloudFlare's API.
 */
abstract class CloudFlareAPI {

  /**
   * HTTP client used for interfacing with the API.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * Last raw response returned from the API.  Intended for debugging only.
   *
   * @var \GuzzleHttp\Message\ResponseInterface;
   */
  private $lastRawResponse;

  /**
   * Last response returned from the API.
   *
   * @var \Drupal\cloudflare\CloudFlarePhpSdk\CloudFlareApiResponse;
   */
  private $lastResponse;

  /**
   * Gets last response returned from the API.
   *
   * @return \GuzzleHttp\Message\ResponseInterface
   *   The last response from the API.
   */
  public function getLastResponse() {
    return $this->lastResponse;
  }

  // Contact "source" property values.
  const REQUEST_TYPE_GET = 'GET';
  const REQUEST_TYPE_POST = 'POST';
  const REQUEST_TYPE_PUT = 'PUT';
  const REQUEST_TYPE_PATCH = 'PATCH';
  const REQUEST_TYPE_DELETE = 'DELETE';
  const API_ENDPOINT_BASE = 'https://api.cloudflare.com/client/v4/';

  // The CloudFlare API sets a maximum of 1,200 requests in a 5-minute period.
  const API_RATE_LIMIT = 1200;

  /**
   * Constructor for the Cloudflare SDK object.
   *
   * Parameters include minimum required credentials for all requests.
   *
   * @param string $apikey
   *   API key generated on the "My Account" page.
   * @param string $email
   *   Email address associated with your CloudFlare account.
   */
  public function __construct($apikey, $email) {
    $this->apikey = $apikey;
    $this->email = $email;
    $this->client = new GuzzleHttp\Client(['base_url' => self::API_ENDPOINT_BASE]);
    $this->client->setDefaultOption('headers', ['X-Auth-Key' => $apikey, 'X-Auth-Email' => $email, 'Content-Type' => 'application/json']);
    $this->client->setDefaultOption('verify', FALSE);
  }


  /**
   * Accepts a HTTP response code and returns the status string for it.
   *
   * @param string $response_code
   *   The HTTP response code returned from the cloudflare API.
   *
   * @return string
   *   String associated with the HTTP code.
   */
  public function responseCodeToStatusString($response_code) {
    switch ($response_code) {
      case '200':
        return 'OK';

      case '304':
        return 'Not Modified';

      case '400':
        return 'Bad Request';

      case '401':
        return 'Unauthorized';

      case '403':
        return 'Forbidden';

      case '429':
        return 'Too many requests';

      case '405':
        return 'Method Not Allowed';

      case '415':
        return 'Unsupported Media Type';
    }
    return 'Unknown Response Code';
  }


  /**
   * Accepts a HTTP response code and returns the description string for it.
   *
   * @param string $response_code
   *   The HTTP response code returned from the cloudflare API.
   *
   * @return string
   *   Description associated with the HTTP code.
   */
  public function responseCodeToDescription($response_code) {
    switch ($response_code) {
      case '200':
        return 'request successful';

      case '304':
        return '';

      case '400':
        return 'request was invalid';

      case '401':
        return 'user does not have permission';

      case '403':
        return 'request not authenticated';

      case '429':
        return 'client is rate limited';

      case '405':
        return 'incorrect HTTP method provided';

      case '415':
        return 'response is not valid JSON';
    }
    return 'Unknown Response Code';
  }


  /**
   * Sends a request to the API.
   *
   * @param string $request_type
   *   The type of HTTP request being made.
   *   Expected to be one of: REQUEST_TYPE_GET, REQUEST_TYPE_POST
   *   REQUEST_TYPE_PATCH, REQUEST_TYPE_PUT or REQUEST_TYPE_DELETE.
   * @param string $api_end_point
   *   The relative url for the endpoint.  All endpoints are assumed to be
   *   relative to 'https://api.cloudflare.com/client/v4/'.
   * @param array|null $request_params
   *   (Optional) Associative array of parameters to be passed with the HTTP
   *   request.
   *
   * @throws \CloudFlareApiException
   *   Exception containing relevant information for a developer.
   */
  protected function makeRequest($request_type, $api_end_point, $request_params = NULL) {
    try {
      switch ($request_type) {
        case self::REQUEST_TYPE_GET:
          $this->lastRawResponse = $this->client->get($api_end_point, ['query' => $request_params]);
          break;

        case self::REQUEST_TYPE_POST:
          $this->lastRawResponse = $this->client->post($api_end_point, ['data' => $request_params]);
          break;

        case self::REQUEST_TYPE_PATCH:
          $this->lastRawResponse = $this->client->patch($api_end_point, ['json' => $request_params]);
          break;

        case self::REQUEST_TYPE_PUT:
          $this->lastRawResponse = $this->client->put($api_end_point, ['json' => $request_params]);
          break;

        case self::REQUEST_TYPE_DELETE:
          $this->lastRawResponse = $this->client->delete($api_end_point, ['json' => $request_params]);
          // json,data
          break;
      }
    }

    catch (\GuzzleHttp\Exception\RequestException $re) {
      $http_response_code = $re->getCode();
      $http_response_message = $re->getMessage();
      throw new CloudFlareApiException($http_response_code, NULL, $http_response_message, $re->getPrevious());
    }

    finally{
      $this->lastResponse = new CloudFlareApiResponse($this->lastResponse()->json());
    }

    if ($this->lastResponse->getStatusCode() != '200') {
      $http_response_code = $this->lastResponse->getStatusCode;
      $http_response_message = $this->lastResponse->getReasonPhrase();
      throw new CloudFlareApiException($http_response_code, NULL, $http_response_message, NULL);
    }
  }

  /**
   * Returns information on the currently logged in/authenticated user.
   */
  public function user() {
    $request_path = 'user';
    $this->makeRequest(self::REQUEST_TYPE_GET, $request_path);
    return new ApiTypes\CloudFlareUser($this->getResponse()->json()['result']);
  }

}
