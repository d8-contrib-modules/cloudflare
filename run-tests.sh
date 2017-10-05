#!/bin/bash

# Copy the program into the drupal installation
mkdir -p docroot/modules/program && rsync -a . docroot/modules/program --exclude \".idea\" --exclude bin --exclude \".git\" --exclude \".gitignore\" --exclude docroot --exclude \"*.make\" --exclude \".travis.yml\" --exclude vendor && rm -fr modules/contrib themes/contrib

# Create required directories in docroot
mkdir -p docroot/profiles
mkdir -p docroot/themes

# move into docroot
cd docroot

# Run everything in a subshell so we can always cleanup
(
  # Exit on fail
  set -e

  # Run unit tests, this will complete very quickly and will catch early failures
  ../vendor/bin/phpunit --group=cloudflare --configuration=core/phpunit.xml.dist modules/program

  # Unit tests passed, boot up a full drupal and run tests
  ../vendor/bin/drush site-install standard --yes --account-pass=admin --db-url=mysql://root:@127.0.0.1/simpletest_db
  ../vendor/bin/drush config-set system.performance css.preprocess 0 --yes
  ../vendor/bin/drush config-set system.performance js.preprocess 0 --yes
  ../vendor/bin/drush config-set system.logging error_level all --yes
  ../vendor/bin/drush en simpletest cloudflare cloudflarepurger --yes

  # Boot up server and client
  ../vendor/bin/drush runserver --default-server=builtin 8888 > /dev/null &
  phantomjs --webdriver=4444 > /dev/null &

  # Run all tests
  php core/scripts/run-tests.sh --module cloudflare --php $(which php) --url http://localhost:8888/ --verbose
  php core/scripts/run-tests.sh --module cloudflarepurger --php $(which php) --url http://localhost:8888/ --verbose
)

# Store the exit status of the subcommand
exit_status=$?

# list jobs to kill
jobs -p

# kill drush server and phantomjs
pkill -P $$

# Exit with the exit status
exit $exit_status