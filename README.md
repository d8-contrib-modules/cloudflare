# CloudFlare
The CloudFlare module provides integration with the CloudFlare CDN using the CloudFlare API see: https://api.cloudflare.com/

Special thanks to Wim Leers and Niels Van Mourik for their collaboration and support on all things cache and purge!

## Disclaimer
The current CloudFlare API only supports 200 tag purge requests/day which makes it unsuitable for a production site.  It's out hope that the limit will be raised in the near future.

## Dependencies
The module relies on the CloudFlarePhpSdk for all interactions with the
CloudFlare API.  You can check it out here:  https://github.com/d8-contrib-modules/cloudflarephpsdk

## Installation
CloudFlare requires the CloudFlarePhpSdk library to be imported via composer.  
You can install one of two ways:

Method A (Recommended):
- At the root of your site run composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha1"
- Install the CloudFlare module.

Method B:
- Install composer manager
- Install the CloudFlare module.
- At the root of your Drupal site, run composer drupal-update.
  This rebuilds core/composer.json and downloads the CloudFlare module's requirements: it downloads the CloudFlarePHPSdk.

## Usage
The module currently provides the following settings to administrative users:
1. API Credentials:  `/admin/config/services/cloudflare`
You can enter your Api key and email address for authenticating to the API.

1. Zone Settings: `/admin/config/services/cloudflare/zone`
Allows you to edit all the CloudFlare zone settings that are editable given the
api credentials and cloudflare account.  Note not all settings are editable.



## Submodules
### CloudFlare Purger
This module is responsible for integration with the purge module.

In D8 cache clearing is a whole new world. 
D8 introduces the concept of cache tags.  Cache tags represent groupings of content that should be purged when content is created, updated or deleted. Most of this already happens behind the scenes so you don't need to rely on extensive rules like in D6/D7. See [here](https://www.drupal.org/developing/api/8/cache/tags) and [here](http://buytaert.net/making-drupal-8-fly) for more info on tags


#### Usage
Assuming that you are not too familiar with the Purge Module to setup you will need to
- Enable `cloudflarepurger` module
- Enable `purge_processor_lateruntime` module
- Enable `purge_queuer_coretags` module
- Enable `purge_ui` module

- If you have not already entered your CloudFlare credentials Visit `/admin/config/services/cloudflare` and add your cloudflare email and apikey.
- Go to `admin/config/development/performance/purge` and click "Add purger" then select "CloudFlare"

  ** Note in the future there will be other processor and queuers provided by purge.  You can enabled and disable as desired, and swap in other queuer's and processors.  Recommend reading the [documentation for Purge](https://www.drupal.org/project/purge)


### CloudFlare Zone Settings UI
This module allows you to manipulate CloudFlare account zone settings from Drupal.  It's intended to also jump-start ideas for
other integrations.  

#### Usage
Go to `admin/config/services/cloudflare/zone` make changes as necessary.



## Contribution and development.  
This module provides a solid foundation for interacting with CloudFlare, it
does not provide complete functionality at this point.  There is a lot that can
be built out in both the D8.  


The module has a 3-tiered architecture
1. CloudFlare API
1. CloudFlare PHPSdk  (The SDK is imported using Composer.)
1. CloudFlare Drupal 8 Module

Keep in mind contributions that add API integrations will likely need to be made to both the Drupal
project AND the PHP SDK.  The projects share maintainers so if you make
contributions to both we will do out best to get them approved in a timely
fashion together.

## Legal
CloudFlare is a trademark of CloudFlare Inc.  This module has not been built,
maintained or supported by CloudFlare Inc.  This is an open source project with
no association with CloudFlare Inc.  The module uses their API, that's all.
