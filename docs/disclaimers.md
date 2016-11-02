## API Rate Limit (Enterprise)
The current CloudFlare API only supports 2000 tag purge requests/day.  This number is suitable for some but not ALL sites.  It's our hope that the limit will be raised in the near future.

## Cache Tag Header Size
Currently CloudFlare does not support 16k cache tag headers which are necessary for taking full advantage of [D8's cache tag system](https://www.drupal.org/developing/api/8/cache/tags).

The current module uses a bloom-filter based approach to work around this limitation. They limit the number of possible cache tags to 4096. However, that means that purging one tag can result in other tags and therefore pages being inadvertently purged.  This makes the module unsuitable for high-traffic events.

## Varnish
The CloudFlare purger has been built as a plugin to the [Purge](https://www.drupal.org/project/purge) module.  
At this time no Varnish purger plugins have been written for the Purge module. They are coming soon! We expect Varnish and [Acquia Purge](https://www.drupal.org/project/acquia_purge) D8 purge plugins to appear sometime in the first half of 2016.

## Drupal.org
This project is built using TravisCI because:
- D.O's testbot does not support composer-based contrib projects.
- D.O's testbot does not support Drupal Code Sniffer.

This project's active development occurs on GitHub:
- Developers are more comfortable with pull-request based contribution than D.O's patch based workflow.
