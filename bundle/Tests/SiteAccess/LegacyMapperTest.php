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
        $bag = new \Symfony\Component\HttpFoundation\ParameterBag();
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
        return array(
            array(
                '/some/pathinfo',
                '/some/pathinfo',
                new SiteAccess('foo', 'default'),
                array(
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/some%C3%BCtf/path',
                '/someütf/path',
                new SiteAccess('foo', 'default'),
                array(
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/env/matching',
                '/env/matching',
                new SiteAccess('foo', 'env'),
                array(
                    'name' => 'foo',
                    'type' => 7,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/urimap/matching',
                '/urimap/matching',
                new SiteAccess('foo', 'uri:map'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/foo/urimap/matching',
                '/urimap/matching',
                new SiteAccess('foo', 'uri:map'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array('foo'),
                ),
            ),
            array(
                '/urielement/matching',
                '/urielement/matching',
                new SiteAccess('foo', 'uri:element'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/foo/bar/urielement/matching',
                '/urielement/matching',
                new SiteAccess('foo', 'uri:element'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array('foo', 'bar'),
                ),
            ),
            array(
                '/foo/bar/baz/urielement/matching',
                '/urielement/matching',
                new SiteAccess('foo', 'uri:element'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array('foo', 'bar', 'baz'),
                ),
            ),
            array(
                '/uritext/matching',
                '/uritext/matching',
                new SiteAccess('foo', 'uri:text'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/uriregex/matching',
                '/uriregex/matching',
                new SiteAccess('foo', 'uri:regexp'),
                array(
                    'name' => 'foo',
                    'type' => 2,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/hostmap/matching',
                '/hostmap/matching',
                new SiteAccess('foo', 'host:map'),
                array(
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/hostelement/matching',
                '/hostelement/matching',
                new SiteAccess('foo', 'host:element'),
                array(
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/hosttext/matching',
                '/hosttext/matching',
                new SiteAccess('foo', 'host:text'),
                array(
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/hostregex/matching',
                '/hostregex/matching',
                new SiteAccess('foo', 'host:regexp'),
                array(
                    'name' => 'foo',
                    'type' => 4,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/port/matching',
                '/port/matching',
                new SiteAccess('foo', 'port'),
                array(
                    'name' => 'foo',
                    'type' => 3,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/custom/matching',
                '/custom/matching',
                new SiteAccess('foo', 'custom_match'),
                array(
                    'name' => 'foo',
                    'type' => 10,
                    'uri_part' => array(),
                ),
            ),
        );
    }

    public function siteAccessParamStringMatchProvider()
    {
        return array(
            array(
                '/some/pathinfo/(param)/foo',
                '/some/pathinfo',
                '/(param)/foo',
                new SiteAccess('foo', 'default'),
                array(
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => array(),
                ),
            ),
            array(
                '/some/pathinfo/(param)/foo/b%C3%A4r',
                '/some/pathinfo',
                '/(param)/foo/bär',
                new SiteAccess('foo', 'default'),
                array(
                    'name' => 'foo',
                    'type' => 1,
                    'uri_part' => array(),
                ),
            ),
        );
    }

    /**
     * @param array $methodsToMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\HttpFoundation\Request
     */
    private function getRequestMock(array $methodsToMock = array())
    {
        return $this
            ->getMockBuilder('Symfony\\Component\\HttpFoundation\\Request')
            ->setMethods(array_merge(array('getPathInfo'), $methodsToMock))
            ->getMock();
    }

    /**
     * @param array $methodsToMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainerMock(array $methodsToMock = array())
    {
        return $this
            ->getMockBuilder('Symfony\\Component\\DependencyInjection\\ContainerInterface')
            ->setMethods($methodsToMock)
            ->getMock();
    }
}
