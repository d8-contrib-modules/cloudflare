# CloudFlare
The CloudFlare module provides integration with the CloudFlare CDN using the CloudFlare API see: https://api.cloudflare.com/

<img alt="Build Status Images" src="https://travis-ci.org/d8-contrib-modules/cloudflare.svg">

Special thanks to Wim Leers and Niels Van Mourik for their collaboration and support on all things cache and purge!

## Current Features
- Cache clearing by Path (Recommended For Free and Professional) and Tag (Enterprise).
- Restore client's original IP address.

## Quick Start Instructions
- [Free Tier](https://github.com/d8-contrib-modules/cloudflare/blob/8.x-1.x/docs/freetier_setup.md)
- [Enterprise Tier](https://github.com/d8-contrib-modules/cloudflare/blob/8.x-1.x/docs/enterprise_setup.md)

## Dependencies
- [CTools D8 Module](https://www.drupal.org/project/ctools)
- [Purge D8 Module](https://www.drupal.org/project/purge) (for purging)
- [URLs Queuer Module](https://www.drupal.org/project/purge_queuer_url) (for free tier or purging by URL only)
- [CloudFlarePhpSdk](https://github.com/d8-contrib-modules/cloudflarephpsdk) - The module relies on the CloudFlarePhpSdk for all interactions with the
CloudFlare API. 

## Support
- Report bugs and request features in the [GitHub CloudFlare Issue Queue](https://github.com/d8-contrib-modules/cloudflare/issues).
- Use pull requests (PRs) to contribute to CloudFlare.

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

## Cache Tags
In D8 cache clearing is a whole new world. D8 introduces the concept of cache
tags.  Cache tags represent groupings of  content that should be purged when
content is created, updated or deleted. Most of this already happens behind the
scenes so you don't need to rely on extensive rules like in D6/D7.
See [here](https://www.drupal.org/developing/api/8/cache/tags)
and [here](http://buytaert.net/making-drupal-8-fly) for more info on tags.  This
module has experimental support for Tags (it will still work with path based purging).

## Note to those updating from earlier version
You will need to update your composer dependencies:
-   `composer require d8-contrib-modules/cloudflarephpsdk "1.0.0-alpha5"`
- You will also need to update to the most recent version of the Purge module.

## Gotcha's, Disclaimers, & Technical Notes
To read more about different details, and gotchas [read more here.](https://github.com/d8-contrib-modules/cloudflare/blob/8.x-1.x/docs/disclaimers.md)

## Running tests
To run tests:
  - composer install
  - ./run-tests.sh

## Legal
CloudFlare is a trademark of CloudFlare Inc.  This module has not been built,
maintained or supported by CloudFlare Inc.  This is an open source project with
no association with CloudFlare Inc.  The module uses their API, that's all.
