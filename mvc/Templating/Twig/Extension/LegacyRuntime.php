<?php
/**
 * File containing the LegacyExtension class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Twig\Extension;

use eZ\Publish\Core\MVC\Legacy\Templating\LegacyHelper;
use Twig_Environment;

/**
 * Twig extension for eZ Publish legacy.
 */
class LegacyRuntime
{
    /**
     * @var \eZ\Publish\Core\MVC\Legacy\Templating\LegacyHelper
     */
    private $legacyHelper;

    /**
     * @var string
     */
    private $jsTemplate;

    /**
     * @var string
     */
    private $cssTemplate;

    /**
     * The Twig environment.
     *
     * @var \Twig_Environment
     */
    private $environment;

    public function __construct(
        Twig_Environment $environment,
        LegacyHelper $legacyHelper,
        $jsTemplate,
        $cssTemplate
    ) {
        $this->environment = $environment;
        $this->legacyHelper = $legacyHelper;
        $this->jsTemplate = $jsTemplate;
        $this->cssTemplate = $cssTemplate;
    }

    /**
     * Generates style tags to be embedded in the page.
     *
     * @return string html script and style tags
     */
    public function renderLegacyJs()
    {
        $jsFiles = [];
        $jsCodeLines = [];

        foreach ($this->legacyHelper->get('js_files', []) as $jsItem) {
            // List of items can contain empty elements, path to files or code
            if (!empty($jsItem)) {
                if (isset($jsItem[4]) && $this->isFile($jsItem, '.js')) {
                    $jsFiles[] = $jsItem;
                } else {
                    $jsCodeLines[] = $jsItem;
                }
            }
        }

        return $this->environment->render(
            $this->jsTemplate,
            [
                'js_files' => $jsFiles,
                'js_code_lines' => $jsCodeLines,
            ]
        );
    }

    /**
     * Generates script tags to be embedded in the page.
     *
     * @return string html script and style tags
     */
    public function renderLegacyCss()
    {
        $cssFiles = [];
        $cssCodeLines = [];

        foreach ($this->legacyHelper->get('css_files', []) as $cssItem) {
            // List of items can contain empty elements, path to files or code
            if (!empty($cssItem)) {
                if (isset($cssItem[5]) && $this->isFile($cssItem, '.css')) {
                    $cssFiles[] = $cssItem;
                } else {
                    $cssCodeLines[] = $cssItem;
                }
            }
        }

        return $this->environment->render(
            $this->cssTemplate,
            [
                'css_files' => $cssFiles,
                'css_code_lines' => $cssCodeLines,
            ]
        );
    }

    /**
     * Is the provided item (path or link) a file or code. Based on legacy's rules (ezjscpacker.php).
     *
     * @param $item string to be tested
     * @param $extension string extension of the file
     *
     * @return bool true if item is a file
     */
    private function isFile($item, $extension)
    {
        return
            strpos($item, 'http://') === 0
            || strpos($item, 'https://') === 0
            || strripos($item, $extension) === (\strlen($item) - \strlen($extension));
    }
}
