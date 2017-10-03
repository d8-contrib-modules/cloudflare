#!/bin/bash

# Copy the program into the drupal installation
mkdir -p docroot/modules/program && rsync -a . docroot/modules/program  --exclude \".idea\" --exclude bin --exclude \".git\" --exclude \".gitignore\" --exclude docroot --exclude \"*.make\" --exclude \".travis.yml\" --exclude vendor && rm -fr modules/contrib themes/contrib

# Run the tests
cd docroot/core && ../../vendor/bin/phpunit --group=cloudflare ../modules/program