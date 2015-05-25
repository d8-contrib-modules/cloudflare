<?php

namespace Drupal\cloudflare;

use Drupal\cloudflare\ApiTypes;
use GuzzleHttp;

class CloudFlareAPI {

  /**
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * @var \GuzzleHttp\Message\ResponseInterface;
   */
  private $response;


  /**
   * @return \GuzzleHttp\Message\ResponseInterface
   */
  public function getResponse() {
    return $this->response;
  }

  // Contact "source" property values.
  const REQUEST_TYPE_GET = 'GET';
  const REQUEST_TYPE_POST = 'POST';
  const REQUEST_TYPE_PUT = 'PUT';
  const REQUEST_TYPE_PATCH= 'PATCH';
  const REQUEST_TYPE_DELETE = 'DELETE';
  const API_ENDPOINT_BASE = 'https://api.cloudflare.com/client/v4/';


  // Zone security levels.
  const ZONE_SECURITY_OFF= 'essentially_off';
  const ZONE_SECURITY_LOW = 'low';
  const ZONE_SECURITY_MEDIUM = 'medium';
  const ZONE_SECURITY_HIGH = 'high';
  const ZONE_SECURITY_UNDERATTACK = 'under_attack';

  // Zone cache levels
  const ZONE_CACHE_SIMPLIFIED = 'simplified';
  const ZONE_CACHE_BASIC = 'basic';
  const ZONE_CACHE_AGGRESSIVE= 'aggressive';

  // Polish settings
  const ZONE_POLISH_OFF = 'off';
  const ZONE_POLISH_LOSSLESS = 'lossless';
  const ZONE_POLISH_LOSSY = 'lossy';


  //The CloudFlare API sets a maximum of 1,200 requests in a five minute period.
  const apiRateLimit = 1200;

  /**
   * Constructor for the Cloudflare SDK object.
   *
   * Parameters include minimum required credentials for all requests.
   *
   * @param string $apikey
   *   API key generated on the "My Account" page
   * @param string $email
   *   Email address associated with your CloudFlare account
   */
  public function __construct($apikey, $email) {
    $this->apikey = $apikey;
    $this->email = $email;
    $this->client = new GuzzleHttp\Client(['base_url' => self::API_ENDPOINT_BASE]);
    $this->client->setDefaultOption('headers', ['X-Auth-Key'=>$apikey, 'X-Auth-Email'=>$email, 'Content-Type'=> 'application/json']);
    $this->client->setDefaultOption('verify', false);

  }


  /**
   * Accepts a HTTP response code and returns the status string for it.
   *
   * @param $responseCode
   *   The HTTP response code returned from the cloudflare API.
   *
   * @return string
   *   String associated with the HTTP code.
   */
  public function responseCodeToStatusString($responseCode) {
    switch($responseCode){
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
   * @param $responseCode
   *   The HTTP response code returned from the cloudflare API.
   *
   * @return string
   *   Description associated with the HTTP code.
   */
  public function responseCodeToDescription($responseCode) {
    switch($responseCode){
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
   * @param $requestType
   * @param $apiEndPoint
   * @param array|null $requestParams
   * (Optional) Associative array of parameters.
   *
   * @throws \CloudFlareApiException
   */
  private function makeRequest($requestType, $apiEndPoint, $requestParams=NULL){
   try {
      switch($requestType){
        case self::REQUEST_TYPE_GET:
          $this->response = $this->client->get($apiEndPoint, ['query' => $requestParams]);
          break;
        case self::REQUEST_TYPE_POST:
          $this->response = $this->client->post($apiEndPoint, ['data' => $requestParams]);
          break;
        case self::REQUEST_TYPE_PATCH:
          $this->response = $this->client->patch($apiEndPoint, ['json' => $requestParams]);
          break;
        case self::REQUEST_TYPE_PUT:
          $this->response = $this->client->put($apiEndPoint,  ['json' => $requestParams]);
          break;
        case self::REQUEST_TYPE_DELETE:
          $this->response = $this->client->delete($apiEndPoint, ['json' => $requestParams]); //json,data
          break;
      }
    }

     catch (\GuzzleHttp\Exception\RequestException $re) {
       $httpResponseCode = $re->getCode();
       $httpResponseMessage = $re ->getMessage();
       throw new CloudFlareApiException($httpResponseCode, NULL, $httpResponseMessage, $re->getPrevious());
     }
    if($this->response->getStatusCode()!='200') {
       $httpResponseCode = $this->response->getStatusCode;
       $httpResponseMessage = $this->response->getReasonPhrase();
       throw new CloudFlareApiException($httpResponseCode, NULL, $httpResponseMessage, NULL);
     }

  }

  public function user() {
    $requestPath = 'user';
    $this->makeRequest(self::REQUEST_TYPE_GET, $requestPath);
    return new ApiTypes\CloudFlareUser($this->getResponse()->json()['result']);
  }

  public function listZones(){
    $requestPath = 'zones';
    $this->makeRequest(self::REQUEST_TYPE_GET, $requestPath);

    $parsedZones = [];
    foreach($this->getResponse()->json()['result'] as $zone) {
      $parsedZones[]=new ApiTypes\CloudFlareZone($zone);
    }
    return $parsedZones;
  }

  public function purgeAllFiles($zoneID){
    $requestPath = strtr('zones/:identifier/purge_cache',[':identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_DELETE, $requestPath,['purge_everything'=>true]);
  }

  public function purgeIndividualFiles($zoneID, array $files){
    $requestPath = strtr('zones/:identifier/purge_cache',[':identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_DELETE, $requestPath, array('files' => $files));
  }

  public function setSecurityLevel($zoneID, $zoneLevel){
    $requestPath = strtr('zones/:zone_identifier/settings/security_level',[':zone_identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $requestPath, ['value'=>$zoneLevel]);
  }

  public function setCacheLevel($zoneID, $cacheLevel) {
    $requestPath = strtr('zones/:zone_identifier/settings/cache_level',[':zone_identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $requestPath, ['value'=>$cacheLevel]);
  }

  public function setBrowserCacheTtl($zoneID, $ttl){
    $requestPath = strtr('zones/:zone_identifier/settings/cache_level',[':zone_identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $requestPath, ['value'=>$ttl]);
  }

  public function setMinify($zoneID, $css, $html, $js){
    $requestPath = strtr('zones/:zone_identifier/settings/minify',[':zone_identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $requestPath, ['value'=>['css'=>$css, 'html'=>$html, 'js'=>$js]]);
  }

  public function setPolish($zoneID, $polish_level){
    $requestPath = strtr('zones/:zone_identifier/settings/polish',[':zone_identifier'=>$zoneID]);
    $this->makeRequest(self::REQUEST_TYPE_PATCH, $requestPath, ['value'=>$polish_level]);
  }
}