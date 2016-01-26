# CloudFlare
The CloudFlare module provides integration with the CloudFlare CDN using the CloudFlare API see: https://api.cloudflare.com/

<img alt="Build Status Images" src="https://travis-ci.org/d8-contrib-modules/cloudflare.svg">

Special thanks to Wim Leers and Niels Van Mourik for their collaboration and support on all things cache and purge!

## Current Features
- Cache clearing by Tag (Recommended) and Path.
- Restore client's original IP address.

## Support
- Report bugs and request features in the [GitHub CloudFlare Issue Queue](https://github.com/d8-contrib-modules/cloudflare/issues).
- Use pull requests (PRs) to contribute to CloudFlare.

## Dependencies
- [CTools D8 Module](https://www.drupal.org/project/ctools)
- [Purge D8 Module](https://www.drupal.org/project/purge)
- [CloudFlarePhpSdk ](https://github.com/d8-contrib-modules/cloudflarephpsdk) - The module relies on the CloudFlarePhpSdk for all interactions with the
CloudFlare API.  You can check it out [here](https://github.com/d8-contrib-modules/cloudflarephpsdk).

## Getting Started
- `drush dl cloudflare purge ctools --yes`
-  From the root of your site run install composer dependencies:
   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha2"`
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

## Disclaimers

### API Rate Limit
The current CloudFlare API only supports 200 tag purge requests/day which makes it unsuitable for a production site.  It's our hope that the limit will be raised in the near future.

### Cache Tag Header Size
Currently CloudFlare does not support 16k cache tag headers which are necessary for taking full advantage of [D8's cache tag system](https://www.drupal.org/developing/api/8/cache/tags).

The current module and CloudFlare itself use a bloom-filter based approach to work around this limitation. They limit the number of possible cache tags to 4096. However, that means that purging one tag can result in other tags and therefore pages being inadvertently purged.  This makes the module unsuitable for high-traffic events.

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
