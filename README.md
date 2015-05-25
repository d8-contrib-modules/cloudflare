## Introduction

Provides integration with the Cloudflare API see: https://api.cloudflare.com/
The module provides an easily extendable PHP SDK to interact with the cloudflare SDK.  By design there is a clear line
of demarkation between the API interface an the Drupal integration.

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
