<?php
/**
 * File containing the LegacyExtension class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Twig\Extension;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension for eZ Publish legacy.
 */
class LegacyExtension extends Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction(
                'ez_legacy_render_js',
                [LegacyRuntime::class, 'renderLegacyJs'],
                ['is_safe' => ['html']]
            ),
            new Twig_SimpleFunction(
                'ez_legacy_render_css',
                [LegacyRuntime::class, 'renderLegacyCss'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
