<?php
/**
 * This file is part of the eZ Publish Kernel package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\Security;

use eZ\Bundle\EzPublishLegacyBundle\Security\SecurityListener;
use eZ\Publish\Core\MVC\Symfony\Security\Tests\EventListener\SecurityListenerTest as BaseTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityListenerTest extends BaseTest
{
    protected function generateListener()
    {
        return new SecurityListener(
            $this->repository,
            $this->configResolver,
            $this->eventDispatcher,
            $this->tokenStorage,
            $this->authChecker
        );
    }

    public function testOnKernelRequestLegacyMode()
    {
        $event = new GetResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(true));

        $this->tokenStorage
            ->expects($this->never())
            ->method('getToken');
        $this->authChecker
            ->expects($this->never())
            ->method('isGranted');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestSubRequestFragment()
    {
        $event = new GetResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/_fragment'),
            HttpKernelInterface::MASTER_REQUEST
        );
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        $this->tokenStorage
            ->expects($this->never())
            ->method('getToken');
        $this->authChecker
            ->expects($this->never())
            ->method('isGranted');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestSubRequest()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        parent::testOnKernelRequestSubRequest();
    }

    public function testOnKernelRequestNoSiteAccess()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        parent::testOnKernelRequestNoSiteAccess();
    }

    public function testOnKernelRequestNullToken()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        parent::testOnKernelRequestNullToken();
    }

    public function testOnKernelRequestLoginRoute()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        parent::testOnKernelRequestLoginRoute();
    }

    public function testOnKernelRequestAccessDenied()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        parent::testOnKernelRequestAccessDenied();
    }

    public function testOnKernelRequestAccessGranted()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        parent::testOnKernelRequestAccessGranted();
    }
}
