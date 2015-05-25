<?php
namespace Drupal\cloudflare\ApiTypes;

class CloudFlareZone{
  private $id;
  private $name;
  private $status;
  private $paused;
  private $type;
  private $development_mode;
  private $nameServers;
  private $originalNameServers;
  private $originalRegistrar;
  private $originalDnshost;
  private $modifiedOn;
  private $createdOn;
  private $meta;
  private $owner;
  private $permissions;
  private $plan;


  public function __construct(array $params) {
    $this->id = $params['id'];
    $this->name = $params['name'];
    $this->status = $params['status'];
    $this->paused = $params['paused'];
    $this->type = $params['type'];
    $this->development_mode = $params['development_mode'];
    $this->name_servers = $params['name_servers'];
    $this->original_name_servers = $params['original_name_servers'];
    $this->original_registrar = $params['original_registrar'];
    $this->original_dnshost = $params['original_dnshost'];
    $this->modified_on = $params['modified_on'];
    $this->created_on = $params['created_on'];
    $this->meta = $params['meta'];
    $this->owner = $params['owner'];
    $this->permissions = $params['permissions'];
    $this->plan = $params['plan'];
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @return mixed
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @return mixed
   */
  public function getPaused() {
    return $this->paused;
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @return mixed
   */
  public function getDevelopmentMode() {
    return $this->development_mode;
  }

  /**
   * @return mixed
   */
  public function getNameServers() {
    return $this->nameServers;
  }

  /**
   * @return mixed
   */
  public function getOriginalNameServers() {
    return $this->originalNameServers;
  }

  /**
   * @return mixed
   */
  public function getOriginalRegistrar() {
    return $this->originalRegistrar;
  }

  /**
   * @return mixed
   */
  public function getOriginalDnshost() {
    return $this->originalDnshost;
  }

  /**
   * @return mixed
   */
  public function getModifiedOn() {
    return $this->modifiedOn;
  }

  /**
   * @return mixed
   */
  public function getCreatedOn() {
    return $this->createdOn;
  }

  /**
   * @return mixed
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * @return mixed
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * @return mixed
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * @return mixed
   */
  public function getPlan() {
    return $this->plan;
  }

  /**
   * @return mixed
   */
  public function getResultInfo() {
    return $this->resultInfo;
  }

  /**
   * @return mixed
   */
  public function getSuccess() {
    return $this->success;
  }

  /**
   * @return mixed
   */
  public function getErrors() {
    return $this->errors;
  }





}