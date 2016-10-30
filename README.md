# CloudFlare
The CloudFlare module provides integration with the CloudFlare CDN using the CloudFlare API see: https://api.cloudflare.com/

<img alt="Build Status Images" src="https://travis-ci.org/d8-contrib-modules/cloudflare.svg">

Special thanks to Wim Leers and Niels Van Mourik for their collaboration and support on all things cache and purge!

## Note to those updating from earlier version
You will need to update your composer dependencies:
-   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha4"`
- You will also need to update to the most recent version of the Purge module.

## Note to those on CloudFlare Free Tier
CloudFlare has limited support for tag clearing to their enterprise tier. To switch to path based purging you will need to:
- uninstall purge_queuer_coretags: `drush dis purge_queuer_coretags`
- install purge_queuer_url  `drush en purge_queuer_url`
- clear stale tag clear requests: `drush ev "delete from queue where name = 'purge'"`
- `drush cset system.performance cache.page.max_age 31536000` (year)
- Empty Drupal's page cache: `drush cache-rebuild`
- Empty Varnish page cache if you have it.
- Empty CloudFlare's cache.

## Current Features
- Cache clearing by Path (Recommended For Free and Professional) and Tag (Enterprise).
- Restore client's original IP address.

## Support
- Report bugs and request features in the [GitHub CloudFlare Issue Queue](https://github.com/d8-contrib-modules/cloudflare/issues).
- Use pull requests (PRs) to contribute to CloudFlare.

## Dependencies
- [CTools D8 Module](https://www.drupal.org/project/ctools)
- [Purge D8 Module](https://www.drupal.org/project/purge) (for purging)
- [URLs Queuer Module](https://www.drupal.org/project/purge_queuer_url) (for free tier or purging by URL only)
- [CloudFlarePhpSdk](https://github.com/d8-contrib-modules/cloudflarephpsdk) - The module relies on the CloudFlarePhpSdk for all interactions with the
CloudFlare API.  You can check it out [here](https://github.com/d8-contrib-modules/cloudflarephpsdk).

## CloudFlare Free Tier vs Enterprise
- Free Tier does not support cache tags.  
- If you attempt to configure tag based purging on Free Tier you will get HTTP 400 error codes in the error log.
- Free Tier don't have a vary by cookie. In English?  That means if you login to
  your site then authenticated pages can get cached by the CDN.  So anonymous users
  users see what appear to be authenticated pages (they are not really authenticated).
  To work around we recommend that you setup a separate domain for authenticated
  users. e.g if your domain is yourdomain.com then setup a second domain for
  authenticated users that bypasses cloudflare e.g `edit.yourdomain.com`. In this setup block access to /user on your public
  domain via .htaccess rules.
- By default CloudFlare will cache static resources but not HTML. You need to add a page rule to cache all the things.

## Getting Started (Free Tier)
- `drush dl cloudflare purge ctools purge_queuer_url --yes`
-  From the root of your site run install composer dependencies:
   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha4"`
- To install: `drush en cloudflare cloudflarepurger purge purge_ui purge_drush purge_queuer_coretags purge_processor_cron --yes`
- *Note*: If you try to install purge, cloudflare and cloudflare_purger all at once via the UI you will get a one time error on install.  This is known issue that is impacting other D8 modules. See
 [here](https://www.drupal.org/node/1387438)
 [here](https://www.drupal.org/node/2315801) and [here](https://www.drupal.org/node/2638320). If you are installing via the UI, recommend the following discrete steps:
 1. install ctools
 1. install purge
 1. install purge_queuer_url
 1. install cloudflare
 1. install cloudflarepurger
 1. install purge_ui purge_drush purge_processor_cron
- `drush cr`
- Go to `admin/config/services/cloudflare` and enter your cloudflare API credentials.
- In most environments you will get the CloudFlare edge server ip returned by default. By checking `Restore Client Ip Address` the module can restore the original client IP address on each request.
- Under `Host to Bypass CloudFlare` you can specify a host used for authenticated users to edit the site that bypasses CloudFlare.  This can help suppress watchdog warnings regarding requests bypassing CloudFlare.
- Head over to `/admin/config/development/performance/purge`
- Click "Add purger" and select "CloudFlare".
- Click "Add".
- `drush cset system.performance cache.page.max_age 31536000` (year)
- Empty Varnish page cache if you have it.
- Empty CloudFlare's cache.
- Now you are ready to go!

## Getting Started (Experimental Cache Tag Support: Enterprise Tier ONLY)
- `drush dl cloudflare purge ctools --yes`
-  From the root of your site run install composer dependencies:
   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha4"`
- To install: `drush en cloudflare cloudflarepurger purge purge_ui purge_drush purge_queuer_coretags purge_processor_cron --yes`
- *Note*: If you try to install purge, cloudflare and cloudflare_purger all at once via the UI you will get a one time error on install.  This is known issue that is impacting other D8 modules. See
 [here](https://www.drupal.org/node/1387438)
 [here](https://www.drupal.org/node/2315801) and [here](https://www.drupal.org/node/2638320). If you are installing via the UI, recommend the following discrete steps:
 1. install ctools
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

## Cache Tags
In D8 cache clearing is a whole new world. D8 introduces the concept of cache
tags.  Cache tags represent groupings of  content that should be purged when
content is created, updated or deleted. Most of this already happens behind the
scenes so you don't need to rely on extensive rules like in D6/D7.
See [here](https://www.drupal.org/developing/api/8/cache/tags)
and [here](http://buytaert.net/making-drupal-8-fly) for more info on tags.  This
module has experimental support for Tags (it will still work with path based purging).


## Disclaimers

### API Rate Limit (Enterprise)
The current CloudFlare API only supports 2000 tag purge requests/day.  This number is suitable for some but not ALL sites.  It's our hope that the limit will be raised in the near future.

### Cache Tag Header Size
Currently CloudFlare does not support 16k cache tag headers which are necessary for taking full advantage of [D8's cache tag system](https://www.drupal.org/developing/api/8/cache/tags).

The current module uses a bloom-filter based approach to work around this limitation. They limit the number of possible cache tags to 4096. However, that means that purging one tag can result in other tags and therefore pages being inadvertently purged.  This makes the module unsuitable for high-traffic events.

### Varnish
The CloudFlare purger has been built as a plugin to the [Purge](https://www.drupal.org/project/purge) module.  
At this time no Varnish purger plugins have been written for the Purge module. They are coming soon! We expect Varnish and [Acquia Purge](https://www.drupal.org/project/acquia_purge) D8 purge plugins to appear sometime in the first half of 2016.

### Drupal.org
This project is built using TravisCI because:
- D.O's testbot does not support composer-based contrib projects.
- D.O's testbot does not support Drupal Code Sniffer.

This project's active development occurs on GitHub:
- Developers are more comfortable with pull-request based contribution that D.O's patch based workflow.

## Legal
CloudFlare is a trademark of CloudFlare Inc.  This module has not been built,
maintained or supported by CloudFlare Inc.  This is an open source project with
no association with CloudFlare Inc.  The module uses their API, that's all.
