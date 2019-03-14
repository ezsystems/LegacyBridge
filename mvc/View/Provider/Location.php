<?php
/**
 * File containing the View\Provider\Location class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\View\Provider;

use eZ\Publish\API\Repository\Values\Content\Content as APIContent;
use eZ\Publish\Core\MVC\Legacy\Templating\LegacyHelper;
use eZ\Publish\Core\MVC\Legacy\View\Provider;
use eZ\Publish\Core\MVC\Symfony\RequestStackAware;
use eZ\Publish\Core\MVC\Symfony\View\LocationValueView;
use eZ\Publish\Core\MVC\Symfony\View\View;
use eZ\Publish\Core\MVC\Symfony\View\ViewProvider;
use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZModule;
use ezpEvent;

class Location extends Provider implements ViewProvider
{
    use RequestStackAware;

    /**
     * Returns a ContentView object corresponding to location found within $view.
     * Will basically run content/view legacy module with appropriate parameters.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\View $view
     *
     * @return \eZ\Publish\Core\MVC\Symfony\View\ContentView|void
     */
    public function getView(View $view)
    {
        if (!$view instanceof LocationValueView || !$view->getLocation() instanceof APILocation) {
            return null;
        }

        $logger = $this->logger;
        $legacyHelper = $this->legacyHelper;
        $currentViewProvider = $this;
        $viewParameters = [];
        $request = $this->getCurrentRequest();
        if (isset($request)) {
            $viewParameters = $request->attributes->get('viewParameters', []);
        }

        $viewType = $view->getViewType();
        $location = $view->getLocation();

        $legacyContentClosure = static function (array $params) use ($location, $viewType, $logger, $legacyHelper, $viewParameters, $currentViewProvider) {
            $content = isset($params['content']) ? $params['content'] : null;
            // Additional parameters (aka user parameters in legacy) are expected to be scalar
            foreach ($params as $paramName => $param) {
                if (!is_scalar($param)) {
                    unset($params[$paramName]);
                    if (isset($logger)) {
                        $logger->notice(
                            "'$paramName' is not scalar, cannot pass it to legacy content module. Skipping.",
                            [__METHOD__]
                        );
                    }
                }
            }

            // viewbaseLayout is useless in legacy views
            unset($params['viewbaseLayout']);
            $params += $viewParameters;

            // Render preview or published view depending on context.
            if (isset($params['isPreview']) && $params['isPreview'] === true && $content instanceof APIContent) {
                return $currentViewProvider->renderPreview($content, $params, $legacyHelper);
            } else {
                return $currentViewProvider->renderPublishedView($location, $viewType, $params, $legacyHelper);
            }
        };

        $this->decorator->setContentView(
            new ContentView($legacyContentClosure)
        );

        return $this->decorator;
    }

    /**
     * Returns published view for $location.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param string $viewType variation of display for your content
     * @param array $params Hash of arbitrary parameters to pass to final view
     * @param \eZ\Publish\Core\MVC\Legacy\Templating\LegacyHelper $legacyHelper
     *
     * @return string
     */
    public function renderPublishedView(APILocation $location, $viewType, array $params, LegacyHelper $legacyHelper)
    {
        $moduleResult = [];

        // Filling up moduleResult
        $result = $this->getLegacyKernel()->runCallback(
            static function () use ($location, $viewType, $params, &$moduleResult) {
                $contentViewModule = eZModule::findModule('content');
                $moduleResult = $contentViewModule->run(
                    'view',
                    [$viewType, $location->id],
                    false,
                    $params
                );

                return ezpEvent::getInstance()->filter('response/output', $moduleResult['content']);
            },
            false
        );

        $legacyHelper->loadDataFromModuleResult($moduleResult);

        return $result;
    }

    /**
     * Returns preview for $content (versionNo to display is held in $content->versionInfo).
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param array $params Hash of arbitrary parameters to pass to final view
     * @param \eZ\Publish\Core\MVC\Legacy\Templating\LegacyHelper $legacyHelper
     *
     * @return string
     */
    public function renderPreview(APIContent $content, array $params, LegacyHelper $legacyHelper)
    {
        /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteAccess */
        $siteAccess = $this->getCurrentRequest()->attributes->get('siteaccess');
        $moduleResult = [];

        // Filling up moduleResult
        $result = $this->getLegacyKernel()->runCallback(
            static function () use ($content, $params, $siteAccess, &$moduleResult) {
                $contentViewModule = eZModule::findModule('content');
                $moduleResult = $contentViewModule->run(
                    'versionview',
                    [$content->contentInfo->id, $content->getVersionInfo()->versionNo, $content->getVersionInfo()->languageCodes[0]],
                    false,
                    ['site_access' => $siteAccess->name] + $params
                );

                return ezpEvent::getInstance()->filter('response/output', $moduleResult['content']);
            },
            false
        );

        $legacyHelper->loadDataFromModuleResult($moduleResult);

        return $result;
    }
}
