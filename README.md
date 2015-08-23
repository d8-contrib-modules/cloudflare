## Introduction
Provides integration with the Cloudflare API see: https://api.cloudflare.com/

[![Automated Build](https://travis-ci.org/d8-contrib-modules/cloudflare.svg?branch=master)](https://travis-ci.org/d8-contrib-modules/cloudflare)
[![License](https://poser.pugx.org/d8-contrib-modules/cloudflare/license)](https://packagist.org/packages/phpunit/phpunit)

The module relies on the CloudFlarePHPSdk for all interactions with the 
CloudFlare API.  You can check it out here:  https://github.com/aweingarten/cloudflarephpsdk

## Installation
- If you don't already have it download and install the Composer Manager module 
  from D.O.
- Initialize it using the init.sh script (or drush composer-manager-init).
  This registers the module's Composer command for Drupal core.
- Install the CloudFlare module.
- Inside your core/ directory run composer drupal-update.
    This rebuilds core/composer.json and downloads the new module's requirements.


## Usage
The module currently provides the following settings to administrative users:
1. API Credentials:  /admin/config/development/cloudflare
You can enter your Api key and email address for authenticating to the API.

1. Zone Settings: /admin/config/development/cloudflare/zone
Allows you to edit all the CloudFlare zone settings that are editable given the
api credentials and cloudflare account.  Note not all settings are editable. 

1. Cache Clearing:  /admin/config/development/cloudflare/cache-clear
Provides form to clear multiple paths or to clear the cache for the entire zone.
Note cloudflare does NOT currently support wildcards for cache clears.  


## Contribution and development.  
This module provides a solid foundation for interacting with CloudFlare, it 
does not provide complete functionality at this point.  There is a lot that can 
be built out in both the D8.  


The module has a 3-tiered architecture
1. CloudFlare API
1. CloudFlare PHPSdk  (The SDK is imported using Composer Manager.)
1. CloudFlare Drupal 8 Module

Keep in mind contributions will likely need to be made to both the Drupal 
project AND the PHP SDK.  The projects share maintainers so if you make 
contributions to both we will do out best to get them approved in a timely 
fashion together.

##Legal
CloudFlare is a trademark of CloudFlare Inc.  This module has not been built,
maintained or supported by CloudFlare Inc.  This is an open source project with 
no association with CloudFlare Inc.  The module uses their API, that's all. 
