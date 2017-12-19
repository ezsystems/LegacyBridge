# Installing the eZ Platform legacy bridge

Unlike eZ Publish 5.x, eZ Platform does not include the Legacy stack by default.

Even though it is not recommended for use on new installs, eZ Publish Legacy can easily be installed
on top of Platform using Composer to provide a more up-to-date platform to migrate your code to eZ Platform with.

### Add the composer `legacy post-*-scripts`

Edit `composer.json`, and add those lines to both `post-update-cmd` and `post-install-cmd` blocks at the end, but before
`eZ\Bundle\EzPublishCoreBundle\Composer\ScriptHandler::dumpAssets`:
```
"scripts": {
    "legacy-scripts": [
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::symlinkLegacyFiles"
    ],
    "post-install-cmd": [
        ...,
        "@legacy-scripts"
    ],
    "post-update-cmd": [
        ...,
        "@legacy-scripts"
    ],
}
```

Example: In the case of stock eZ Platform 2.0 and higher that would specifically be:
```
"scripts": {
    "legacy-scripts": [
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::symlinkLegacyFiles"
    ],
    "symfony-scripts": [
        "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::buildBootstrap",
        "eZ\\Bundle\\EzPublishCoreBundle\\Composer\\ScriptHandler::clearCache",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installAssets",
        "@legacy-scripts",
        "eZ\\Bundle\\EzPublishCoreBundle\\Composer\\ScriptHandler::dumpAssets",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installRequirementsFile"
    ],
    "post-install-cmd": [
        "@symfony-scripts"
    ],
    "post-update-cmd": [
        "@symfony-scripts"
    ],
    (...)
```


### Enable EzPublishLegacyBundle and EzSystemsEzPlatformXmlTextFieldTypeBundle
Edit `app/AppKernel.php`, and add `new eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle( $this ),`
at the end of  the `$bundles` array. Pay close attention to the `$this` argument, LegacyBundle is a bit 
spoiled and has high expectations from its collaborators ;)

If you still plan to have content in XmlText _(as opposed to migrating to RichText straight away)_, then
you'll also need to enable `EzSystems\EzPlatformXmlTextFieldTypeBundle\EzSystemsEzPlatformXmlTextFieldTypeBundle`
bundle, which gets installed together with Legacy Bridge.

### Add legacy routes
Edit `app/config/routing.yml`, and add the LegacyBundle routes at the end of the file.

```
# NOTE: Always keep at the end of the file so native symfony routes always have precendence, to avoid legacy
# REST pattern overriding possible eZ Platform REST routes. 
_ezpublishLegacyRoutes:
    resource: @EzPublishLegacyBundle/Resources/config/routing.yml
```

### Enable legacy_mode for your backoffice siteaccess

The Legacy Backoffice requires the `legacy_mode` option to be enabled.
This can be done in app/config/config.yml or app/config/ezplatform.yml:

```
ez_publish_legacy:
    system:
        site_admin:
           legacy_mode: true
```

### Optional: add security rules for the Setup Wizard

If you intend to run the legacy setup wizard, you need to allow it in `app/config/security.yml`.

```
ezpublish_setup:
    pattern: ^/ezsetup
    security: false
```

_Tip:_
> Enabling Setup wizard is only needed if you intend to perform a new install with legacy demo data, you can also install Plaform data _(clean, demo)_ and afterwards when everything is setup use Platform UI to change Richtext FieldTypes to XmlText _(using [ezplatform-xmltext-fieldtype](https://github.com/ezsystems/ezplatform-xmltext-fieldtype))_, or install [Netgen RichTextDataType Bundle for legacy](https://github.com/netgen/NetgenRichTextDataTypeBundle) to make legacy allow raw editing of these. If you install eZ Platform Enterprise and it's demo data, there will also be Landing Page field type to handle in similar way _(contributions to Legacy Bridge on this more than welcome ;))_


### Install `ezsystems/legacy-bridge`

`ezsystems/legacy-bridge` contains a newer version of the libraries previously included in `ezsystems/ezpublish-kernel` in version 5.x.

```
composer require --update-no-dev "ezsystems/legacy-bridge:^2.0"
```

### Optional: Add missing legacy extensions

Several ezpublish-legacy extensions are no longer installed by default with ezpublish-legacy package, such as ezfind or eztags.
They can still be manually added to the root `composer.json` of your eZ Platform installation.

Previusly bundled extensions, and their composer package names for installing:
- ezsystems/ezscriptmonitor-ls
- ezsystems/ezsi-ls
- ezsystems/ezfind-ls
- ezsystems/eztags-ls

To add the package(s) you want, add them with: `composer require --update-no-dev <package>...`

### Configure virtual host rewrite rules

To access legacy assets (eZ Publish designs and extension designs), add the following rewrite rules to your Apache virtual host:

```
# If using cluster, uncomment the following two lines:
#RewriteRule ^/var/([^/]+/)?storage/images(-versioned)?/.* /app.php [L]
#RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* /index_cluster.php [L]

RewriteRule ^/var/([^/]+/)?storage/images(-versioned)?/.* - [L]
RewriteRule ^/var/([^/]+/)?cache/(texttoimage|public)/.* - [L]
RewriteRule ^/design/[^/]+/(stylesheets|images|javascript|fonts)/.* - [L]
RewriteRule ^/share/icons/.* - [L]
RewriteRule ^/extension/[^/]+/design/[^/]+/(stylesheets|flash|images|lib|javascripts?)/.* - [L]
RewriteRule ^/packages/styles/.+/(stylesheets|images|javascript)/[^/]+/.* - [L]
RewriteRule ^/packages/styles/.+/thumbnail/.* - [L]
RewriteRule ^/var/storage/packages/.* - [L]
```

Or if using nginx:

```
# If using cluster, uncomment the following two lines:
#rewrite "^/var/([^/]+/)?storage/images(-versioned)?/(.*)" "/app.php" break;
#rewrite "^/var/([^/]+/)?cache/(texttoimage|public)/(.*)" "/index_cluster.php" break;

rewrite "^/var/([^/]+/)?storage/images(-versioned)?/(.*)" "/var/$1storage/images$2/$3" break;
rewrite "^/var/([^/]+/)?cache/(texttoimage|public)/(.*)" "/var/$1cache/$2/$3" break;
rewrite "^/design/([^/]+)/(stylesheets|images|javascript|fonts)/(.*)" "/design/$1/$2/$3" break;
rewrite "^/share/icons/(.*)" "/share/icons/$1" break;
rewrite "^/extension/([^/]+)/design/([^/]+)/(stylesheets|flash|images|lib|javascripts?)/(.*)" "/extension/$1/design/$2/$3/$4" break;
rewrite "^/packages/styles/(.+)/(stylesheets|images|javascript)/([^/]+)/(.*)" "/packages/styles/$1/$2/$3/$4" break;
rewrite "^/packages/styles/(.+)/thumbnail/(.*)" "/packages/styles/$1/thumbnail/$2" break;
rewrite "^/var/storage/packages/(.*)" "/var/storage/packages/$1" break;
```

### Setup Folder Permissions

Last step, if you are on *nix operation system, is to make sure to run 
the appropriate command for setting correct folder permissions, you 
can find the information you need in [installation guide for eZ Platform](https://doc.ezplatform.com/en/latest/getting_started/install_ez_platform/).
