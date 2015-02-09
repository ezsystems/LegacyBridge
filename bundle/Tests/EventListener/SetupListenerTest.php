<?php
/**
 * This file is part of the legacy-bridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Bundle\EzPublishLegacyBundle\Tests\EventListener;

use eZ\Bundle\EzPublishCoreBundle\EventListener\RequestEventListener;
use eZ\Bundle\EzPublishLegacyBundle\EventListener\SetupListener;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SetupListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var RequestEventListener
     */
    private $requestEventListener;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var GetResponseEvent
     */
    private $event;

    /**
     * @var HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpKernel;

    protected function setUp()
    {
        parent::setUp();

        $this->configResolver = $this->getMock( 'eZ\Publish\Core\MVC\ConfigResolverInterface' );
        $this->router = $this->getMock( 'Symfony\Component\Routing\RouterInterface' );
        $this->logger = $this->getMock( 'Psr\\Log\\LoggerInterface' );

        $this->requestEventListener = new SetupListener( $this->router, 'foobar' );

        $this->request = $this
            ->getMockBuilder( 'Symfony\\Component\\HttpFoundation\\Request' )
            ->setMethods( array( 'getSession', 'hasSession' ) )
            ->getMock();

        $this->httpKernel = $this->getMock( 'Symfony\\Component\\HttpKernel\\HttpKernelInterface' );
        $this->event = new GetResponseEvent(
            $this->httpKernel,
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    public function testSubscribedEvents()
    {
        $this->assertSame(
            array(
                KernelEvents::REQUEST => array(
                    array( 'onKernelRequestSetup', 190 ),
                )
            ),
            $this->requestEventListener->getSubscribedEvents()
        );
    }

    public function testOnKernelRequestSetupSubrequest()
    {
        $this->router
            ->expects( $this->never() )
            ->method( 'getContext' );
        $this->router
            ->expects( $this->never() )
            ->method( 'setContext' );

        $event = new GetResponseEvent( $this->httpKernel, new Request, HttpKernelInterface::SUB_REQUEST );
        $this->requestEventListener->onKernelRequestSetup( $event );
        $this->assertFalse( $event->hasResponse() );
    }

    public function testOnKernelRequestSetupAlreadyHasSiteaccess()
    {
        $event = new GetResponseEvent( $this->httpKernel, new Request, HttpKernelInterface::MASTER_REQUEST );
        $this->requestEventListener->onKernelRequestSetup( $event );
        $this->assertFalse( $event->hasResponse() );
    }

    public function testOnKernelRequestSetupAlreadySetupUri()
    {
        $this->router
            ->expects( $this->once() )
            ->method( 'generate' )
            ->with( 'ezpublishSetup' )
            ->will( $this->returnValue( '/setup' ) );
        $this->router
            ->expects( $this->once() )
            ->method( 'getContext' )
            ->will( $this->returnValue( $this->getMock( 'Symfony\Component\Routing\RequestContext' ) ) );

        $requestEventListener = new RequestEventListener( $this->configResolver, $this->router, 'setup', $this->logger );
        $event = new GetResponseEvent( $this->httpKernel, Request::create( '/setup' ), HttpKernelInterface::MASTER_REQUEST );
        $requestEventListener->onKernelRequestSetup( $event );
        $this->assertFalse( $event->hasResponse() );
    }

    public function testOnKernelRequestSetup()
    {
        $this->router
            ->expects( $this->once() )
            ->method( 'generate' )
            ->with( 'ezpublishSetup' )
            ->will( $this->returnValue( '/setup' ) );
        $this->router
            ->expects( $this->once() )
            ->method( 'getContext' )
            ->will( $this->returnValue( $this->getMock( 'Symfony\Component\Routing\RequestContext' ) ) );

        $requestEventListener = new RequestEventListener( $this->configResolver, $this->router, 'setup', $this->logger );
        $event = new GetResponseEvent( $this->httpKernel, Request::create( '/foo/bar' ), HttpKernelInterface::MASTER_REQUEST );
        $requestEventListener->onKernelRequestSetup( $event );
        $this->assertTrue( $event->hasResponse() );
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf( 'Symfony\Component\HttpFoundation\RedirectResponse', $response );
        $this->assertSame( '/setup', $response->getTargetUrl() );
    }
}
