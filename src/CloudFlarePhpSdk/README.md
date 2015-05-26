## Introduction

Provides integration with the Cloudflare API see: https://api.cloudflare.com/
The module provides an easily extendable PHP SDK to interact with the cloudflare SDK.  
By design there is a clear separation of concerns everything inside the CloudFlarePhpSdk
namespace is concerned with interacting with the CloudFlare API.  It knows 
nothing about Drupal.

## Usage

The current implementation is a POC.   The inital focus is to solidify the PHP cloudflare API interface.
Once the interface is finalized the Drupal intergration will be built out.
Some quick examples of how to use the POC API:

```
$api_key = 'your_cloudflare_api_key';
$user_email = 'your_cloudflare_email'
$api = new \Drupal\cloudflare\CloudFlareAPI($api_key, $user_email );
$zone_id = $api->listZones()[0]->getId();
$result = $api->purgeIndividualFiles($zone_id, array('path1'));
$result = $api->setSecurityLevel($zone_id, 'low');
```


## Structure
### CloudFlareAPIBase.php
Provides facility for making webservice calls to cloudflare.  It provides a
wrapper around guzzle so that people using this module do not need to concern
themselves with the low-level implementation details of guzzle.

### CloudFlareApiException
This exception is thrown by the CloudFlarePhpSdk when something unexpected 
happens. Contains application level responses codes and messages that can be
handled at higher levels of the application stack. 

The CloudFlarePhpSdk integration layer has a primary purpose of interfacing
with CloudFlare. In order to focus on this task it knows nothing about drupal. 
It does no exception handling on it's own. It also does not return booleans for
success.  The assumption is that methods are successful.  If they are not an
CloudFlareApiException will be thrown with application level responses codes
and messages that a dev can use.


### ApiEndPoints
Extend CloudFlareAPIBase for specific endpoints.  Provides all the tools you 
need to interface with those endpoints, getters, setters, and constants. 

### ApiTypes
Parses incoming data from the API into types data structures.  Creating typed
classes for the incoming data makes working with the API a lot simpler for Devs.
It takes away the guess work for what's in an array.  That also means that a 
Dev using this will not need to re-read the cloudflare API if they don't want
to.