# Installing the eZ Platform legacy bridge

Instructions below will take you through installing legacy bridge and implicit legacy on top of a eZ Platform 1.13.x - 2.x
install.

_TIP:_
> Before starting make sure to check-in (e.g. to Git) your eZ Platform project working space so you'll be able to see & verify changes applied to your setup separate from initial clean project install.

### Install `ezsystems/legacy-bridge` and run `init` command

1. Installed package using [Composer](https://getcomposer.org/doc/00-intro.md):
```
composer require --update-no-dev "ezsystems/legacy-bridge"
```

2. Enable `EzPublishLegacyBundle`
Edit `app/AppKernel.php`, and add `new eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle( $this ),`
at the end of the `$bundles` array _(typically just after `new AppBundle\AppBundle(),`)_.

_NOTE: Pay close attention to the `$this` argument, LegacyBundle needs it to interact with other eZ bundles._

3. Run the following command to configure your install for legacy usage:
```
php app/console ezpublish:legacy:init
```

During it's execution it will advice you on which command to run **after** you have moved over your legacy files
_(extensions, settings and optionally designs)_.

_TIP:_
> If you are in need of applying these "init" steps manually, see INSTALL.md from a 1.4 or a 2.0 version of LegacyBridge.


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
can find the information you need in [installation guide for eZ Publish 5.x](https://doc.ez.no/display/EZP/Installation).
