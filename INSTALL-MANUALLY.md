# Installing the eZ Platform legacy bridge

Instructions below will take you through installing legacy bridge and implicit legacy on top of a eZ Platform 2.x
install.

_TIP:_
> Before starting make sure to check-in (e.g. to Git) your eZ Platform project working space so you'll be able to see & verify changes applied to your setup separate from initial clean project install.

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

Example: In the case of stock eZ Platform 2.5LTS to make sure legacy scripts are run before `assetic:dump`:
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
        "eZ\\Bundle\\EzPublishCoreBundle\\Composer\\ScriptHandler::clearCache",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installAssets",
        "Sensio\\Bundle\\DistributionBundle\\Composer\\ScriptHandler::installRequirementsFile",
        "@php bin/console bazinga:js-translation:dump 'web/assets' --merge-domains",
        "@legacy-scripts"
        "@php bin/console assetic:dump",
        "@php bin/security-checker security:check",
        "yarn install",
        "yarn encore dev"
    ],
    "post-install-cmd": [
        "@symfony-scripts"
    ],
    "post-update-cmd": [
        "@symfony-scripts"
    ],
    (...)
```


### Enable EzPublishLegacyBundle
Edit `app/AppKernel.php`, and add `new eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle( $this ),`
at the end of the `$bundles` array _(typically just after `new AppBundle\AppBundle(),`)_.

_NOTE: Pay close attention to the `$this` argument, LegacyBundle needs it to interact with other eZ bundles._

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

This can be done in app/config/config.yml or app/config/ezplatform.yml, where `site_admin` is the name of the admin
siteaccess:

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
> Enabling Setup wizard is only needed if you intend to perform a new install with legacy demo data, you can also install Platform data _(clean, demo)_ and afterwards when everything is setup use Platform UI to change Richtext FieldTypes to XmlText _(using [ezplatform-xmltext-fieldtype](https://github.com/ezsystems/ezplatform-xmltext-fieldtype))_, or install [Netgen RichTextDataType Bundle for legacy](https://github.com/netgen/NetgenRichTextDataTypeBundle) to make legacy allow raw editing of these. If you install eZ Platform Enterprise and it's demo data, there will also be Landing Page field type to handle in similar way _(contributions to Legacy Bridge on this more than welcome ;))_


### Install `ezsystems/legacy-bridge`


Package can be installed using Composer in the following way:

```
composer require --update-with-all-dependencies "ezsystems/legacy-bridge:^1.5.0"
```

### Recommended: Add additional Legacy <=> eZ Platform integrations

**[netgen/ezplatformsearch](https://github.com/netgen/ezplatformsearch)**

When using either Solr or _(future)_ Elastic search bundle in eZ Platform, this extension makes sure eZ Platform and eZ Publish Legacy use the same search index. Technically it represents eZ Platform search engine as a eZ Publish Legacy search engine called "ezplatformsearch" which is then used for search, indexing and delayed cronjob indexing.

**[netgen/richtext-datatype-bundle](https://github.com/netgen/NetgenRichTextDataTypeBundle)**

If you plan to migrate some or all of your content types to eZ Platform RichText format, install this bundle to make it possible to edit content with RichText field types using raw XML editing text box in eZ Publish Legacy. Good for cases where content migration, and also backend migration needs to happen gradually.

### Optional: Add missing legacy extensions

Several ezpublish-legacy extensions are no longer installed by default with ezpublish-legacy package, such as ezfind or eztags.
They can still be manually added to the root `composer.json` of your eZ Platform installation.

Previusly bundled extensions, and their composer package names for installing:
- ezsystems/ezscriptmonitor-ls
- ezsystems/ezsi-ls
- ezsystems/ezfind-ls
- ezsystems/eztags-ls

To add the package(s) you want, add them with: `composer require --update-with-all-dependencies <package>...`

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
