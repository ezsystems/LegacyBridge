#!/bin/sh
# Install packages using composer
composer install --dev --prefer-dist
# Copy default test configuration
cp config.php-DEVELOPMENT config.php
