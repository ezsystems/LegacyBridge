<?php
/**
 * File containing the LegacyMapperTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\SiteAccess;

use eZ\Publish\Core\MVC\Legacy\Tests\LegacyBasedTestCase;
use eZ\Bundle\EzPublishLegacyBundle\LegacyMapper\SiteAccess as LegacyMapper;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelWebHandlerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class LegacyMapperTest extends LegacyBasedTestCase
{
    private $systemErrorLevel;

    protected function setUp()
    {
        parent::setUp();

        // Silence E_DEPRECATED to avoid issues with notices from legacy in regards to constructors
        $this->systemErrorLevel = error_reporting(E_ALL & ~E_DEPRECATED);
    }

    protected function tearDown()
    {
        error_reporting($this->systemErrorLevel);
        parent::tearDown();
    }

    /**
     * @dataProvider siteAccessMatchProvider
     */
    public function testOnSiteAccessMatch($pathinfo, $semanticPathinfo, SiteAccess $siteaccess, $expectedAccess)
    {
        $container = $this->getContainerMock();
        $container
            ->expects($this->exactly(1))
            ->method('get')
            ->with('ezpublish.siteaccess')
            ->will($this->returnValue($siteaccess));

        $request = $this->getRequestMock();
        $request
            ->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue($pathinfo));
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);

        $mapper = new LegacyMapper();
        $mapper->setContainer($container);
        $bag = new ParameterBag();
        $mapper->onBuildKernelWebHandler(
            new PreBuildKernelWebHandlerEvent(
                $bag,
                $request
            )
        );
        self::assertSame($expectedAccess, $bag->get('siteaccess'));
    }

    /**
     * @dataProvider siteAccessParamStringMatchProvider
     */
    public function testOnSiteAccessMatchUriPart($pathinfo, $semanticPathinfo, $viewParametersString, SiteAccess $siteaccess, $expectedAccess)
    {
        $container = $this->getContainerMock();
        $container
            ->expects($this->exactly(1))
            ->method('get')
            ->with('ezpublish.siteaccess')
            ->will($this->returnValue($siteaccess));

        $request = $this->getRequestMock();
        $request
            ->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue($pathinfo));
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        $request->attributes->set('viewParametersString', $viewParametersString);

        $mapper = new LegacyMapper();
        $mapper->setContainer($container);
        $bag = new \Symfony\Component\HttpFoundation\ParameterBag();
        $mapper->onBuildKernelWebHandler(
            new PreBuildKernelWebHandlerEvent(
                $bag,
                $request
            )
        );
        self::assertSame($expectedAccess, $bag->get('siteaccess'));
    }

    public function siteAccessMatchProvider()
    {
        // args: $pathinfo, $semanticPathinfo, $siteaccess, $expectedAccess
        return [
            [
                '/some/pathinfo',
                '/some/pathinfo',
                new SiteAccess('foo', 'default'),
                [
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => [],
                ],
            ],
            [
                '/some%C3%BCtf/path',
                '/someütf/path',
                new SiteAccess('foo', 'default'),
                [
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => [],
                ],
            ],
            [
                '/env/matching',
                '/env/matching',
                new SiteAccess('foo', 'env'),
                [
                    'name' => 'foo',
                    'type' => 7,
                    'uri_part' => [],
                ],
            ],
            [
                '/urimap/matching',
                '/urimap/matching',
                new SiteAccess('foo', 'uri:map'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => [],
                ],
            ],
            [
                '/foo/urimap/matching',
                '/urimap/matching',
                new SiteAccess('foo', 'uri:map'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo'],
                ],
            ],
            [
                '/foo/',
                '/',
                new SiteAccess('foo', 'uri:map'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo'],
                ],
            ],
            [
                '/foo',
                '/',
                new SiteAccess('foo', 'uri:map'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo'],
                ],
            ],
            [
                '/urielement/matching',
                '/urielement/matching',
                new SiteAccess('foo', 'uri:element'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => [],
                ],
            ],
            [
                '/foo/bar/urielement/matching',
                '/urielement/matching',
                new SiteAccess('foo', 'uri:element'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo', 'bar'],
                ],
            ],
            [
                '/foo/bar/baz/urielement/matching',
                '/urielement/matching',
                new SiteAccess('foo', 'uri:element'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo', 'bar', 'baz'],
                ],
            ],
            [
                '/foo/',
                '/',
                new SiteAccess('foo', 'uri:element'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo'],
                ],
            ],
            [
                '/foo',
                '/',
                new SiteAccess('foo', 'uri:element'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => ['foo'],
                ],
            ],
            [
                '/uritext/matching',
                '/uritext/matching',
                new SiteAccess('foo', 'uri:text'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => [],
                ],
            ],
            [
                '/uriregex/matching',
                '/uriregex/matching',
                new SiteAccess('foo', 'uri:regexp'),
                [
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => [],
                ],
            ],
            [
                '/hostmap/matching',
                '/hostmap/matching',
                new SiteAccess('foo', 'host:map'),
                [
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => [],
                ],
            ],
            [
                '/hostelement/matching',
                '/hostelement/matching',
                new SiteAccess('foo', 'host:element'),
                [
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => [],
                ],
            ],
            [
                '/hosttext/matching',
                '/hosttext/matching',
                new SiteAccess('foo', 'host:text'),
                [
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => [],
                ],
            ],
            [
                '/hostregex/matching',
                '/hostregex/matching',
                new SiteAccess('foo', 'host:regexp'),
                [
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => [],
                ],
            ],
            [
                '/port/matching',
                '/port/matching',
                new SiteAccess('foo', 'port'),
                [
                    'name' => 'foo',
                    'type' => 3,
                    'uri_part' => [],
                ],
            ],
            [
                '/custom/matching',
                '/custom/matching',
                new SiteAccess('foo', 'custom_match'),
                [
                    'name' => 'foo',
                    'type' => 10,
                    'uri_part' => [],
                ],
            ],
            [
                '/_fragment',
                '/',
                new SiteAccess('site', 'default'),
                [
                    'name' => 'site',
                    'type' => 1,
                    'uri_part' => [],
                ],
            ],
        ];
    }

    public function siteAccessParamStringMatchProvider()
    {
        return [
            [
                '/some/pathinfo/(param)/foo',
                '/some/pathinfo',
                '/(param)/foo',
                new SiteAccess('foo', 'default'),
                [
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => [],
                ],
            ],
            [
                '/some/pathinfo/(param)/foo/b%C3%A4r',
                '/some/pathinfo',
                '/(param)/foo/bär',
                new SiteAccess('foo', 'default'),
                [
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => [],
                ],
            ],
        ];
    }

    /**
     * @param array $methodsToMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\HttpFoundation\Request
     */
    private function getRequestMock(array $methodsToMock = [])
    {
        return $this
            ->getMockBuilder(Request::class)
            ->setMethods(array_merge(['getPathInfo'], $methodsToMock))
            ->getMock();
    }

    /**
     * @param array $methodsToMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainerMock(array $methodsToMock = [])
    {
        return $this
            ->getMockBuilder(ContainerInterface::class)
            ->setMethods($methodsToMock)
            ->getMock();
    }
}
