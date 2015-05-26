<?php

namespace Drupal\cloudflare\CloudFlarePhpSdk;

use Drupal\cloudflare\CloudFlarePhpSdk\ApiTypes;
use Drupal\cloudflare;
use GuzzleHttp;

class CloudFlareZoneEndPoint extends CloudFlareAPI{

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


  function __construct($apikey, $email){
    parent::__construct($apikey, $email);
  }

  public function listZones(){
    $requestPath = 'zones';
    $this->makeRequest(self::REQUEST_TYPE_GET, $requestPath);

    $parsedZones = [];
    foreach($this->getResponse()->json()['result'] as $zone) {
      $parsedZones[]=new CloudFlareZone($zone);
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