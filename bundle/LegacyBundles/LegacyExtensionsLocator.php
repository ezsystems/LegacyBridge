<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\LegacyBundles;

use DirectoryIterator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LegacyExtensionsLocator implements LegacyExtensionsLocatorInterface
{
    public function getExtensionDirectories($bundlePath)
    {
        $bundlePath = rtrim($bundlePath, '/\\');
        $legacyPath = "$bundlePath/ezpublish_legacy/";

        if (!is_dir($legacyPath)) {
            return [];
        }

        $return = [];
        /** @var $item DirectoryIterator */
        foreach (new DirectoryIterator($legacyPath) as $item) {
            if (!$item->isDir() || $item->isDot()) {
                continue;
            }

            if (file_exists($item->getPathname() . '/ezinfo.php') || file_exists($item->getPathname() . '/extension.xml')) {
                $return[] = $item->getPathname();
            }
        }

        return $return;
    }

    public function getExtensionNames(BundleInterface $bundle)
    {
        $extensions = $this->getExtensionDirectories($bundle->getPath());
        array_walk(
            $extensions,
            static function (&$path) {
                $path = basename($path);
            }
        );

        if ($bundle instanceof LegacyBundleInterface) {
            $extensions = array_merge(
                $extensions,
                $bundle->getLegacyExtensionsNames()
            );
        }

        return $extensions;
    }
}
