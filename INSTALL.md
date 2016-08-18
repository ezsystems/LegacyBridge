# Installing the eZ Platform legacy bridge

Unlike eZ Publish 5.x, eZ Platform does not include the Legacy stack by default.
Even though it is not officially supported, eZ Publish Legacy can easily be installed
on top of Platform using Composer.

### Missing legacy extensions

Several ezpublish-legacy extensions are no longer installed by default, such as ezfind or eztags.
They can still be manually added to the root `composer.json`.

### Add the composer `legacy post-*-scripts`

Edit `composer.json`, and add those lines to both `post-update-cmd` and `post-install-cmd` blocks at the end:

```
"scripts": {
    "post-install-cmd": [
        ...,
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads"
    ],
    "post-update-cmd": [
        ...,
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installAssets",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::installLegacyBundlesExtensions",
        "eZ\\Bundle\\EzPublishLegacyBundle\\Composer\\ScriptHandler::generateAutoloads"
    ],
}
```

### Enable EzPublishLegacyBundle
Edit `app/AppKernel.php` (or `ezpublish/EzPublishKernel` before ezplatform 2015.12),
and add `new eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle( $this )` at the end of 
the `$bundles` array. Pay close attention to the `$this` argument. The LegacyBundle is a bit 
of a spoiled brat, and has high expectations from its collaborators.

### Add legacy routes
Edit `app/config/routing.yml`, and add the LegacyBundle routes.

```
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

### Configure ezpublish legacy's location in `composer.json`

`ezsystems/ezpublish-legacy`` needs to be installed in a particular location to work.

Edit `composer.json`, and add `"ezpublish-legacy-dir": "ezpublish_legacy"` to the `extra` array:

```
    "extra": {
        "ezpublish-legacy-dir": "ezpublish_legacy",
```

### Install `ezsystems/legacy-bridge`

**Make sure you have set the ezpublish legacy folder in composer.json, as instructed above**

`ezsystems/legacy-bridge` contains the libraries previous included in `ezsystems/ezpublish-kernel`.

It must be installed using Composer:

```
composer require --update-no-dev ezsystems/legacy-bridge
```

### Setup Folder Permissions

Last step, if you are on *nix operation system, is to make sure to run 
the appropriate command for setting correct folder permissions, you 
can find the information you need in installation guide for eZ Publish 5.x.
