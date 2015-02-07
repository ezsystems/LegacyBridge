#!/bin/sh
echo "> Configuring legacy-bridge"

composer require --no-update "legacy-bridge:dev-master"

php ./bin/.travis/enablelegacybundle.php

# Load LegacyBundle routes
echo << EOF >> ezpublish/config/routing.yml

_ezpublishLegacyRoutes:
    resource: '@EzPublishLegacyBundle/Resources/config/routing.yml'

EOF

# legacy mode
echo << EOF >> ezpublish/config/ezpublish.yml

ez_publish_legacy:
    system:
        behat_site_admin:
            legacy_mode: true

EOF

# setup firewall rule
echo << EOF >> ezpublish/config/security.yml

        ezpublish_setup:
            pattern: ^/ezsetup
            security: false

EOF

# Enabled eztpl templating engine
sed -i "s/'twig'/'eztpl', 'twig'/" ezpublish/config/config.yml


./bin/.travis/prepare_ezpublish.sh

# Replace legacy-bridge with the one from the pull-request
rm -rf vendor/ezsystems/legacy-bridge
mv "$BRANCH_BUILD_DIR" vendor/ezsystems/legacy-bridge

# Run setup wizard
php bin/behat --profile setupWizard --suite $INSTALL
php ezpublish/console assetic:dump --env=behat --no-debug
