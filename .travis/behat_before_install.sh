#!/bin/sh
export BRANCH_BUILD_DIR=$TRAVIS_BUILD_DIR
export TRAVIS_BUILD_DIR="$HOME/build/ezpublish-community"
cd "$HOME/build"

# Change the branch and/or remote to use a different ezpublish-community branch
git clone --depth 1 --single-branch --branch EZP-23934-remove_legacy_bridge https://github.com/ezsystems/ezpublish-community.git
cd ezpublish-community

# Use this if you depend on another branch for a dependency (only works for the ezsystems remote)
# (note that packagist may take time to update the references, leading to errors. Just retrigger the build)
#- composer require --no-update dev-MyCustomBranch
./bin/.travis/prepare_system.sh
./bin/.travis/prepare_sahi.sh
