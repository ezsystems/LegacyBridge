<?php
/**
 * File containing the LoaderString class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Twig;

use Twig_LoaderInterface;
use Twig_ExistsLoaderInterface;
use Twig_Source;

/**
 * This loader is supposed to directly load templates as a string, not from FS.
 *
 * {@inheritdoc}
 */
class LoaderString implements Twig_LoaderInterface, Twig_ExistsLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext($name)
    {
        return new Twig_Source($name, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return true;
    }

    /**
     * Returns true if $name is a string template, false if $name is a template name (which should be loaded by Twig_Loader_Filesystem.
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists($name)
    {
        $suffix = '.twig';
        $endsWithSuffix = strtolower(substr($name, -\strlen($suffix))) === $suffix;

        return !$endsWithSuffix;
    }
}
