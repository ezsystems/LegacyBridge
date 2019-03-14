<?php
/**
 * This file is part of the legacy-bridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\EventListener;

use eZ\Bundle\EzPublishLegacyBundle\EventListener\SetupListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use PHPUnit\Framework\TestCase;

class SetupListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Routing\RouterInterface
     */
    private $router;

    protected function setUp()
    {
        parent::setUp();
        $this->router = $this->createMock(RouterInterface::class);
    }

    public function testSubscribedEvents()
    {
        $this->assertSame(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequestSetup', 190],
                ],
            ],
            $this->getListener()->getSubscribedEvents()
        );
    }

    public function testOnKernelRequestSetupSubrequest()
    {
        $this->router->expects($this->never())->method('getContext');
        $this->router->expects($this->never())->method('setContext');

        $event = $this->createEvent(null, HttpKernelInterface::SUB_REQUEST);
        $this->getListener()->onKernelRequestSetup($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestSetupAlreadyHasSiteaccess()
    {
        $event = $this->createEvent();
        $this->getListener()->onKernelRequestSetup($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestSetupAlreadySetupUri()
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('ezpublishSetup')
            ->will($this->returnValue('/setup'));
        $this->router
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($this->createMock(RequestContext::class)));

        $event = $this->createEvent('/setup');
        $this->getListener('setup')->onKernelRequestSetup($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestSetup()
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('ezpublishSetup')
            ->will($this->returnValue('/setup'));
        $this->router
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($this->createMock(RequestContext::class)));

        $event = $this->createEvent('/foo/bar');
        $this->getListener('setup')->onKernelRequestSetup($event);
        $this->assertTrue($event->hasResponse());
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/setup', $response->getTargetUrl());
    }

    /**
     * @param string $uri
     * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
     */
    private function createEvent($uri = null, $requestType = HttpKernelInterface::MASTER_REQUEST)
    {
        return new GetResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $uri !== null ? Request::create($uri) : new Request(),
            $requestType
        );
    }

    /**
     * @param string $siteaccess
     *
     * @return \eZ\Bundle\EzPublishLegacyBundle\EventListener\SetupListener
     */
    private function getListener($siteaccess = 'foobar')
    {
        return new SetupListener($this->router, $siteaccess);
    }
}
