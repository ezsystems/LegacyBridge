<?php
/**
 * File containing the PreviewControllerTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Bundle\EzPublishLegacyBundle\Tests\Controller;

use eZ\Bundle\EzPublishLegacyBundle\Controller\PreviewController;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\Symfony\Controller\Tests\Controller\Content\PreviewControllerTest as BasePreviewControllerTest;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class PreviewControllerTest extends BasePreviewControllerTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configResolver;

    protected function setUp()
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
    }

    /**
     * @return \eZ\Bundle\EzPublishLegacyBundle\Controller\PreviewController
     */
    protected function getPreviewController()
    {
        $controller = new PreviewController(
            $this->contentService,
            $this->httpKernel,
            $this->previewHelper,
            $this->authorizationChecker,
            $this->locationProvider,
            $this->controllerChecker
        );
        $controller->setConfigResolver($this->configResolver);

        return $controller;
    }

    protected function getDuplicatedRequest(Location $location, Content $content, SiteAccess $previewSiteAccess)
    {
        $request = parent::getDuplicatedRequest(
            $location,
            $content,
            $previewSiteAccess
        );
        $request->attributes->set('_controller', 'ezpublish_legacy.controller:indexAction');

        return $request;
    }

    public function testPreview()
    {
        $this->configResolver
            ->expects($this->any())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(true));
        parent::testPreview();
    }
}
