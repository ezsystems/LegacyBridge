<?php
/**
 * File containing the ScriptHandler class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Bundle\EzPublishLegacyBundle\Composer;

use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as DistributionBundleScriptHandler;
use Composer\Script\Event;

class ScriptHandler extends DistributionBundleScriptHandler
{
    /**
     * Installs the legacy assets under the web root directory.
     *
     * For better interoperability, assets are copied instead of symlinked by default.
     *
     * Even if symlinks work on Windows, this is only true on Windows Vista and later,
     * but then, only when running the console with admin rights or when disabling the
     * strict user permission checks (which can be done on Windows 7 but not on Windows
     * Vista).
     *
     * @param $event CommandEvent A instance
     */
    public static function installAssets(Event $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ($options['symfony-assets-install'] === 'symlink') {
            $symlink = '--symlink ';
        } elseif ($options['symfony-assets-install'] === 'relative') {
            $symlink = '--symlink --relative ';
        }

        if (!self::isDir($appDir, 'symfony-app-dir')) {
            return;
        }

        if (!self::isDir($webDir, 'symfony-web-dir')) {
            return;
        }

        static::executeCommand($event, $appDir, 'ezpublish:legacy:assets_install ' . $symlink . escapeshellarg($webDir));
    }

    public static function installLegacyBundlesExtensions(Event $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        $symlink = '';
        if ($options['symfony-assets-install'] === 'relative') {
            $symlink = '--relative ';
        }

        if (!self::isDir($appDir, 'symfony-app-dir')) {
            return;
        }

        static::executeCommand($event, $appDir, 'ezpublish:legacybundles:install_extensions ' . $symlink);
    }

    public static function generateAutoloads(Event $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!self::isDir($appDir, 'symfony-app-dir')) {
            return;
        }

        static::executeCommand($event, $appDir, 'ezpublish:legacy:script bin/php/ezpgenerateautoloads.php');
    }

    public static function generateKernelOverrideAutoloads(Event $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!self::isDir($appDir, 'symfony-app-dir')) {
            return;
        }

        static::executeCommand($event, $appDir, 'ezpublish:legacy:script bin/php/ezpgenerateautoloads.php -o');
    }

    public static function symlinkLegacySrcFiles(Event $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        $srcFolder = '';
        if (isset($options['legacy-src-folder'])) {
            $srcFolder = $options['legacy-src-folder'];
        }

        if (!self::isDir($appDir, 'symfony-app-dir')) {
            return;
        }

        static::executeCommand($event, $appDir, 'ezpublish:legacy:symlink_src -c ' . $srcFolder);
    }

    private static function isDir($dir, $composerSetting)
    {
        if (is_dir($dir)) {
            return true;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        echo "The ${composerSetting} (${dir}) specified in composer.json was not found in " . getcwd();
        echo ', can not execute: ' . $trace[0]['function'] . PHP_EOL;

        return false;
    }
}
