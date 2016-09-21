<?php
/**
 * File containing the SiteAccessTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\LegacyMapper;

use eZ\Bundle\EzPublishLegacyBundle\LegacyMapper\SiteAccess as SiteAccessMapper;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelWebHandlerEvent;
use eZSiteAccess;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class SiteAccessTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    private $systemErrorLevel;

    protected function setUp()
    {
        parent::setUp();

        // Silence E_DEPRECATED to avoid issues with notices from legacy in regards to constructors
        $this->systemErrorLevel = error_reporting(E_ALL & ~E_DEPRECATED);

        $this->request = $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->setMethods(['getPathInfo'])
            ->getMock();
    }

    protected function tearDown()
    {
        error_reporting($this->systemErrorLevel);
        parent::tearDown();
    }

    public function buildKernelProvider()
    {
        return [
            [
                '/(Foo)/Bar',
                '/Media/Images/Admin/EZP-26181',
                '/Admin/Media/Images/Admin/EZP-26181/(Foo)/Bar',
                ['Admin'],
            ],
            [
                '',
                '/Media/Images/Ädmin/EZP-26181',
                '/Ädmin/Media/Images/Ädmin/EZP-26181',
                ['Ädmin'],
            ],
            [
                '',
                '/Media/Images/EZP-26181',
                '/Ädmin/Media/Images/EZP-26181',
                ['Ädmin'],
            ],
            [
                '',
                '/Admin/Media',
                '/Admin/Media/Admin/Media',
                ['Admin', 'Media'],
            ],
            [
                '',
                '/Admin/Media/Admin/Media',
                '/Admin/Media/Admin/Media/Admin/Media',
                ['Admin', 'Media'],
            ],
            [
                '',
                '/A/B',
                '/A/B/A/B',
                ['A', 'B'],
            ],
            [
                '',
                '/A/B/A/B',
                '/A/B/A/B',
                [],
            ],
        ];
    }

    /**
     * @dataProvider buildKernelProvider
     */
    public function testOnBuildKernelWebHandler($viewParams, $semanticPathInfo, $pathInfo, $uriPart)
    {
        $this->request->attributes->set('viewParametersString', $viewParams);
        $this->request->attributes->set('semanticPathinfo', $semanticPathInfo);

        $this->request
            ->expects($this->once())
            ->method('getPathInfo')
            ->will($this->returnValue($pathInfo));

        $siteAccess = $this
            ->getMockBuilder('eZ\Publish\Core\MVC\Symfony\SiteAccess')
            ->setConstructorArgs(['Admin', eZSiteAccess::TYPE_URI, null])
            ->getMock();

        $containerMock = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerInterface');
        $containerMock
            ->expects($this->once())
            ->method('get')
            ->with('ezpublish.siteaccess')
            ->will($this->returnValue($siteAccess));

        $siteAccessMapper = new SiteAccessMapper([]);
        $siteAccessMapper->setContainer($containerMock);
        $event = new PreBuildKernelWebHandlerEvent(new ParameterBag(), $this->request);

        $siteAccessMapper->onBuildKernelWebHandler($event);
        $this->assertSame(
            $uriPart,
            $event->getParameters()->get('siteaccess[uri_part]', null, true)
        );
    }
}
