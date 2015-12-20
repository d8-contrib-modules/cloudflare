# CloudFlare
The CloudFlare module provides integration with the CloudFlare CDN using the CloudFlare API see: https://api.cloudflare.com/


Special thanks to Wim Leers and Niels Van Mourik for their collaboration and support on all things cache and purge!

## Current Features
- Cache clearing by Tag (Recommended) and Path.
- Restore client's original IP address.

## Getting Started
- `drush dl cloudflare purge --yes`
-  From the root of your site run install composer dependencies:
   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha1"`
- To install: `drush en cloudflare cloudflarepurger purge purge_ui purge_drush purge_queuer_coretags purge_processor_cron --yes`
- *Note*: If you try to install purge, cloudflare and cloudflare_purger all at once via the UI you will get a one time error on install.  This is known issue that is impacting other D8 modules. See
 [here](https://www.drupal.org/node/1387438)
 [here](https://www.drupal.org/node/2315801) and [here](https://www.drupal.org/node/2638320). If you are installing via the UI, recommend the following discrete steps:
 1. install purge
 1. install cloudflare
 1. install cloudflarepurger
 1. install purge_ui purge_drush purge_queuer_coretags purge_processor_cron
- `drush cr`
- Go to `admin/config/services/cloudflare` and enter your cloudflare API credentials.
- In most environments you will get the CloudFlare edge server ip returned by default. By checking `Restore Client Ip Address` the module can restore the original client IP address on each request.
- Under `Host to Bypass CloudFlare` you can specify a host used for authenticated users to edit the site that bypasses CloudFlare.  This can help suppress watchdog warnings regarding requests bypassing CloudFlare.
- Head over to `/admin/config/development/performance/purge`
- Click "Add purger" and select "CloudFlare".
- Click "Add".
- Now you are ready to go!

## Disclaimers

### API Rate Limit
The current CloudFlare API only supports 200 tag purge requests/day which makes it unsuitable for a production site.  It's our hope that the limit will be raised in the near future.

### Cache Tag Header Size
Currently CloudFlare does not support 16k cache tag headers which are necessary for taking full advantage of [D8's cache tag system](https://www.drupal.org/developing/api/8/cache/tags).

The current module and CloudFlare itself use a bloom-filter based approach to work around this limitation. They limit the number of possible cache tags to 4096. However, that means that purging one tag can result in other tags and therefore pages being inadvertently purged.  This makes the module unsuitable for high-traffic events.


### Varnish
The CloudFlare purger has been built as a plugin to the [Purge](https://www.drupal.org/project/purge) module.  
At this time no Varnish purger plugins have been written for the Purge module. They are coming soon! We expect Varnish and [Acquia Purge](https://www.drupal.org/project/acquia_purge) D8 purge plugins to appear sometime in the first half of 2016.


## Dependencies
The module relies on the CloudFlarePhpSdk for all interactions with the
CloudFlare API.  You can check it out [here](https://github.com/d8-contrib-modules/cloudflarephpsdk).

The CloudFlare Purger submodule also relies upon the [Purge](https://www.drupal.org/project/purge) module for all cache expiration/purging.

## Usage
The module currently provides the following settings to administrative users:

1. API Credentials:  `/admin/config/services/cloudflare`
You can enter your API key and email address for authenticating to the API.

1. General Configuration
`/admin/config/services/cloudflare`
 - `Restore Client Ip Address` - You can tell the module to restore the original client IP address. In most environments you will get the CloudFlare edge server ip returned by default.
 - `Host to Bypass CloudFlare` You can specify a host used for authenticated users to edit the site that bypasses CloudFlare.
This will help suppress watchdog warnings regarding requests bypassing CloudFlare.

1. Zone Settings: `/admin/config/services/cloudflare/zone`
Allows you to edit all the CloudFlare zone settings that are editable given the
api credentials and cloudflare account.  Note not all settings are editable.



## Submodules
### CloudFlare Purger
This module is responsible for integration with the purge module.

In D8 cache clearing is a whole new world.
D8 introduces the concept of cache tags.  Cache tags represent groupings of content that should be purged when content is created, updated or deleted. Most of this already happens behind the scenes so you don't need to rely on extensive rules like in D6/D7. See [here](https://www.drupal.org/developing/api/8/cache/tags) and [here](http://buytaert.net/making-drupal-8-fly) for more info on tags

  ** Note in the future there will be other processor and queuers provided by purge.  You can enabled and disable as desired, and swap in other queuer's and processors.  Recommend reading the [documentation for Purge](https://www.drupal.org/project/purge)

#### Testing that things are working:
1. Publish a page from your site.
1. Attempt to view the page from your browser as an anonymous user.
1. curl the page `curl -SLIXGET https://test.me/test | grep CF-Cache-Status`
1. You should see a line that says `CF-Cache-Status: Hit`.  If you don't do it then your page is not being cached by CloudFlare. You should follow up with the CloudFlare documentation to ensure that your account is configured properly.
1. Edit the test page inside Drupal.
1. curl the page `curl -SLIXGET https://test.me/test | grep CF-Cache-Status`
1. You should not see a `CF-Cache-Status: Miss`.  This indicates that the cache has been cleared at CloudFlare.  If you do not then CloudFlare purge may be configured incorrectly.  Recommend checking watchdog for clues.


### CloudFlare Zone Settings UI
This module allows you to manipulate CloudFlare account zone settings from Drupal.  It's intended to also jump-start ideas for
other integrations.  

#### Usage
Go to `admin/config/services/cloudflare/zone` make changes as necessary.



## Contribution and Development
This module provides a solid foundation for interacting with CloudFlare, it
does not provide complete functionality at this point.  There is a lot that can
be built out in both the D8.  


The module has a 3-tiered architecture

1. CloudFlare API
1. CloudFlare PHPSdk  (The SDK is imported using Composer)
1. CloudFlare Drupal 8 Module

Keep in mind contributions that add API integrations will likely need to be made to both the Drupal
project AND the PHP SDK.  The projects share maintainers so if you make
contributions to both we will do out best to get them approved in a timely
fashion together.

## Legal
CloudFlare is a trademark of CloudFlare Inc.  This module has not been built,
maintained or supported by CloudFlare Inc.  This is an open source project with
no association with CloudFlare Inc.  The module uses their API, that's all.
