## Introduction
Provides integration with the Cloudflare API see: https://api.cloudflare.com/

[![Automated Build](https://travis-ci.org/d8-contrib-modules/cloudflare.svg?branch=master)](https://travis-ci.org/d8-contrib-modules/cloudflare)
[![License](https://poser.pugx.org/d8-contrib-modules/cloudflare/license)](https://packagist.org/packages/d8-contrib-modules/cloudflare)

The module relies on the CloudFlarePhpSdk for all interactions with the 
CloudFlare API.  You can check it out here:  https://github.com/d8-contrib-modules/cloudflarephpsdk

## Installation
CloudFlare requires a composer php library.  
You can install one of two ways:

Method A:
- At the root of your site run composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha1"
- Install the CloudFlare module.

Method B:
- Install composer manager
- Install the CloudFlare module.
- At the root of your Drupal site, run composer drupal-update. 
  This rebuilds core/composer.json and downloads the CloudFlare module's requirements: it downloads the CloudFlarePHPSdk.

## Submodules

### CloudFlare Purger
This module is responsible for integration with the purge module.  In order to setup you will need to
- Enable `cloudflarepurger` module
- Enable `purge_processor_lateruntime` module
- Enable `purge_queuer_coretags` module
- Visit `/admin/config/services/cloudflare` and add your cloudflare email and apikey.

** Note in the future there will be other processor and queuers provided by purge.

### CloudFlare Zone Settings UI
This module allows you to manipulate CloudFlare account zone settings from Drupal.  It's intended to also jump-start ideas for
other integrations.

## Usage
The module currently provides the following settings to administrative users:
1. API Credentials:  `/admin/config/services/cloudflare`
You can enter your Api key and email address for authenticating to the API.

1. Zone Settings: `/admin/config/services/cloudflare/zone`
Allows you to edit all the CloudFlare zone settings that are editable given the
api credentials and cloudflare account.  Note not all settings are editable. 

## Contribution and development.  
This module provides a solid foundation for interacting with CloudFlare, it 
does not provide complete functionality at this point.  There is a lot that can 
be built out in both the D8.  


The module has a 3-tiered architecture
1. CloudFlare API
1. CloudFlare PHPSdk  (The SDK is imported using Composer.)
1. CloudFlare Drupal 8 Module

Keep in mind contributions will likely need to be made to both the Drupal 
project AND the PHP SDK.  The projects share maintainers so if you make 
contributions to both we will do out best to get them approved in a timely 
fashion together.

##Legal
CloudFlare is a trademark of CloudFlare Inc.  This module has not been built,
maintained or supported by CloudFlare Inc.  This is an open source project with 
no association with CloudFlare Inc.  The module uses their API, that's all. 
