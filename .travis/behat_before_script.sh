#!/bin/sh
echo "> Require legacy-bridge"
composer require legacy-bridge:dev-master

./bin/.travis/prepare_ezpublish.sh

# Replace legacy-bridge with the one from the pull-request
rm -rf vendor/ezsystems/legacy-bridge
mv "$BRANCH_BUILD_DIR" vendor/ezsystems/legacy-bridge

# Run setup wizard
php bin/behat --profile setupWizard --suite $INSTALL
php ezpublish/console assetic:dump --env=behat --no-debug
