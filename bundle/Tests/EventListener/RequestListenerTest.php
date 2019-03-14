<?php
/**
 * File containing the RequestListenerTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Bundle\EzPublishLegacyBundle\EventListener\RequestListener;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Security\User as CoreUser;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $tokenStorage;

    protected function setUp()
    {
        parent::setUp();
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->repository = $this->createMock(Repository::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                KernelEvents::REQUEST => 'onKernelRequest',
            ],
            Requestlistener::getSubscribedEvents()
        );
    }

    public function testOnKernelRequest()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(true));
        $userService = $this->createMock(UserService::class);
        $this->repository
            ->expects($this->once())
            ->method('getUserService')
            ->will($this->returnValue($userService));

        $userId = 123;
        $apiUser = $this->createMock(User::class);
        $userService
            ->expects($this->once())
            ->method('loadUser')
            ->with($userId)
            ->will($this->returnValue($apiUser));
        $this->repository
            ->expects($this->once())
            ->method('setCurrentUser')
            ->with($apiUser);

        $session = $this->createMock(SessionInterface::class);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($session));
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->will($this->returnValue(true));
        $session
            ->expects($this->once())
            ->method('has')
            ->with('eZUserLoggedInID')
            ->will($this->returnValue(true));
        $session
            ->expects($this->once())
            ->method('get')
            ->with('eZUserLoggedInID')
            ->will($this->returnValue($userId));

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will(
                $this->returnValue($token)
            );
        $token
            ->expects($this->once())
            ->method('setUser')
            ->with($this->isInstanceOf(CoreUser::class));

        $event = new GetResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
        $listener = new RequestListener($this->configResolver, $this->repository, $this->tokenStorage);
        $listener->onKernelRequest($event);
    }
}
