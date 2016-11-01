## Getting Started (Experimental Cache Tag Support: Enterprise Tier ONLY)
- `drush dl cloudflare purge ctools --yes`
-  From the root of your site run install composer dependencies:
   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha5"`
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
