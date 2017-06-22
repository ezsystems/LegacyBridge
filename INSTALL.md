# Installing the eZ Platform legacy bridge

Unlike eZ Publish 5.x, eZ Platform does not include the Legacy stack by default.
eZ Publish Legacy can easily be installed on top of Platform using Composer.

### eZ Plaftorm installation

We are assuming you have a fresh [eZ Platform installation here](https://www.ezplatform.com/), 
that basically you have run:

```bash
php -d memory_limit=-1 composer create-project ezsystems/ezplatform
php app/console doctrine:database:create
php app/console ezplatform:install clean
```

At this point you should be able to reach Platform UI at the URL http://{YOURPROJECTHOST}/ez

### Legacy Bridge Installation Steps

#### Adapt your composer.json

Edit `composer.json`, and add those lines to both `post-update-cmd`, `post-install-cmd` and `extra` blocks at the end:

```json
"scripts": {
    "post-install-cmd": [
        ...,
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "Novactive\\EzLegacyToolsBundle\\Composer\\ScriptHandler::installLegacyBundlesSettings",
        "Novactive\\EzLegacyToolsBundle\\Composer\\ScriptHandler::executeLegacyScripts",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateKernelOverrideAutoloads",
    ],
    "post-update-cmd": [
        ...,
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "Novactive\\EzLegacyToolsBundle\\Composer\\ScriptHandler::installLegacyBundlesSettings",
        "Novactive\\EzLegacyToolsBundle\\Composer\\ScriptHandler::executeLegacyScripts",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateKernelOverrideAutoloads",
    ],
    ...,
    "extra": {
        ...,
        "ezpublish-legacy-dir": "ezpublish_legacy",
        "legacy-settings-install": ["force", "relative"],
        "legacy-scripts-execution": []
   } 
}
```

#### Enable the new bundles

Edit `app/AppKernel.php` (or `ezpublish/EzPublishKernel` before ezplatform 2015.12), and add 

```php
new Novactive\EzLegacyToolsBundle\NovactiveEzLegacyToolsBundle(),
new EzSystems\EzPlatformXmlTextFieldTypeBundle\EzSystemsEzPlatformXmlTextFieldTypeBundle(),
new eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle( $this )

```
at the end of the `$bundles` array. 
Pay close attention to the `$this` argument. The LegacyBundle is a bit  of a spoiled brat, and has high expectations 
from its collaborators.


#### Add legacy routes

Edit `app/config/routing.yml`, and add the LegacyBundle routes.

```yaml
_ezpublishLegacyRoutes:
    resource: @EzPublishLegacyBundle/Resources/config/routing.yml
```

#### Install the bundles

**Make sure you have set the ezpublish legacy folder in composer.json, as instructed above**

```bash
composer require "ezsystems/legacy-bridge:1.1.x" "novactive/ezlegacy-tools-bundle:dev-master" "ezsystems/ezplatform-xmltext-fieldtype" netgen/ngsymfonytools netgen/ezplatformsearch --no-scripts
```

#### Create you legacy bundle

```bash
php app/console generate:bundle --namespace=Acme/Bundle/LegacyBundle --format=annotation --bundle-name=AcmeLegacyBundle --dir=src -n
```

Create 2 folders for legacy

```bash
cd src/Acme/Bundle/LegacyBundle
mkdir ezpublish_legacy legacy_settings
cd -
```

Copy the config.php-RECOMMENDED in legacy_settings

```bash
cp ezpublish_legacy/config.php-RECOMMENDED src/Acme/Bundle/LegacyBundle/legacy_settings/config.php
```
In eZ Publish 5, Symfony app folder was named `ezpublish`. This was changed in eZ Platform, 
and now the folder name is `app`, which is Symfony recommended name. 
eZ Publish Legacy supports both of these folder names, however, `ezpublish` is still the default 
one in latest tagged release (v2015.01.3). This means that you need to make eZ Publish Legacy aware of the 
new folder name. You can do this by editing `config.php` and uncommenting:

```php
<?php

define( 'EZP_APP_FOLDER_NAME', 'app' );
```

#### Configure you administration siteaccess.

The Legacy Backoffice requires the `legacy_mode` option to be enabled.
This can be done in app/config/config.yml or app/config/ezplatform.yml:

We are using here: administration as SiteAccessName

```yaml

# app/config/ezplatform.yml

ezpublish:
    repositories:
        default:
            storage: ~
            search:
                engine: %search_engine%
                connection: default
    siteaccess:
        list: [site, administration]
        groups:
            site_group: [site, administration]
        default_siteaccess: site
        match:
            URIElement: 1

    system:
        site_group:
            cache_pool_name: '%cache_pool%'
            var_dir: var/site
            languages: [eng-GB]
            
            
ez_publish_legacy:
    system:
        administration:
           legacy_mode: true
```


Because your are not running the wizard you have to manually create the settings for you legacy siteaccess.
We are providing a template:
```bash
cp -r vendor/ezsystems/legacy-bridge/settings_template/* src/Acme/Bundle/LegacyBundle/legacy_settings
```

#### Finalize the installation (as we did --no-scripts before)

```bash
composer install
```

#### Configure virtual host rewrite rules

To access legacy assets (eZ Publish designs and extension designs), add the following rewrite rules to your Apache virtual host:

```
# If using cluster, uncomment the following two lines:
#RewriteRule ^/var/([^/]+/)?storage/images(-versioned)?/.* /index.php [L]
#RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* /index_cluster.php [L]

RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* - [L]
RewriteRule ^/design/[^/]+/(stylesheets|images|javascript|fonts)/.* - [L]
RewriteRule ^/share/icons/.* - [L]
RewriteRule ^/extension/[^/]+/design/[^/]+/(stylesheets|flash|images|lib|javascripts?)/.* - [L]
RewriteRule ^/packages/styles/.+/(stylesheets|images|javascript)/[^/]+/.* - [L]
RewriteRule ^/packages/styles/.+/thumbnail/.* - [L]
RewriteRule ^/var/storage/packages/.* - [L]
```

> Please refer to doc/nginx for more information (complete vhost without these rules you need)

Or if using nginx:

```
# If using cluster, uncomment the following two lines:
#rewrite "^/var/([^/]+/)?storage/images(-versioned)?/(.*)" "/index.php" break;
#rewrite "^/var/([^/]+/)?cache/(texttoimage|public)/(.*)" "/index_cluster.php" break;

rewrite "^/var/([^/]+/)?cache/(texttoimage|public)/(.*)" "/var/$1cache/$2/$3" break;
rewrite "^/design/([^/]+)/(stylesheets|images|javascript|fonts)/(.*)" "/design/$1/$2/$3" break;
rewrite "^/share/icons/(.*)" "/share/icons/$1" break;
rewrite "^/extension/([^/]+)/design/([^/]+)/(stylesheets|flash|images|lib|javascripts?)/(.*)" "/extension/$1/design/$2/$3/$4" break;
rewrite "^/packages/styles/(.+)/(stylesheets|images|javascript)/([^/]+)/(.*)" "/packages/styles/$1/$2/$3/$4" break;
rewrite "^/packages/styles/(.+)/thumbnail/(.*)" "/packages/styles/$1/thumbnail/$2" break;
rewrite "^/var/storage/packages/(.*)" "/var/storage/packages/$1" break;
```

> Please refer to doc/nginx for more information (complete vhost without these rules you need)

!! Restart your webserver !!

> Yes, don't forget ;-)

### Test !

*/ez* for Plaftform UI
*/administration* for Legacy Admin


### Setup Folder Permissions

Last step, if you are on *nix operation system, is to make sure to run 
the appropriate command for setting correct folder permissions, you 
can find the information you need in installation guide for eZ Publish 5.x.


### Missing legacy extensions

Several ezpublish-legacy extensions are no longer installed by default, such as ezfind or eztags.
They can still be manually added to the root `composer.json`.
