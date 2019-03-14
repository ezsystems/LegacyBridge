<?php
/**
 * File containing the SSOListenerTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Tests\Security;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\Core\MVC\Legacy\Security\Firewall\SSOListener;
use eZ\Publish\Core\MVC\Symfony\Security\User;
use eZ\Publish\Core\Repository\Values\User\User as CoreUser;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use eZUser;
use ezpKernelHandler;
use Closure;

class SSOListenerTest extends TestCase
{
    /**
     * @var \eZ\Publish\Core\MVC\Legacy\Security\Firewall\SSOListener
     */
    private $ssoListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $legacyKernel;

    /**
     * @var \Closure
     */
    private $legacyKernelClosure;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userService;

    protected function setUp()
    {
        parent::setUp();

        $this->ssoListener = new SSOListener(
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(AuthenticationManagerInterface::class),
            'firewall_key'
        );

        $legacyKernel = $this->legacyKernel = $this->createMock(ezpKernelHandler::class);
        $this->legacyKernelClosure = static function () use ($legacyKernel) {
            return $legacyKernel;
        };

        $this->userService = $this->createMock(UserService::class);
    }

    public function testGetPreAuthenticatedDataNoUser()
    {
        $this->ssoListener->setLegacyKernelClosure($this->legacyKernelClosure);
        $this->ssoListener->setUserService($this->userService);

        $this->userService
            ->expects($this->never())
            ->method('loadUser');

        $this->legacyKernel
            ->expects($this->once())
            ->method('runCallback')
            ->with($this->isInstanceOf(Closure::class), false)
            ->will($this->returnValue(null));

        $refListener = new ReflectionObject($this->ssoListener);
        $refMethod = $refListener->getMethod('getPreAuthenticatedData');
        $refMethod->setAccessible(true);
        $this->assertSame(['', ''], $refMethod->invoke($this->ssoListener, new Request()));
    }

    public function testGetPreAuthenticatedData()
    {
        $this->ssoListener->setLegacyKernelClosure($this->legacyKernelClosure);
        $this->ssoListener->setUserService($this->userService);

        $userId = 123;
        $passwordHash = md5('password');
        // Specifically silence E_DEPRECATED on constructor name for php7
        $legacyUser = @new eZUser(['contentobject_id' => $userId]);
        $apiUser = new CoreUser(
            [
                'passwordHash' => $passwordHash,
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            [
                                'contentInfo' => new ContentInfo(),
                            ]
                        ),
                    ]
                ),
            ]
        );

        $finalUser = new User($apiUser, ['ROLE_USER']);

        $this->userService
            ->expects($this->once())
            ->method('loadUser')
            ->with($userId)
            ->will($this->returnValue($apiUser));

        $this->legacyKernel
            ->expects($this->once())
            ->method('runCallback')
            ->with($this->isInstanceOf('\Closure'), false)
            ->will($this->returnValue($legacyUser));

        $refListener = new ReflectionObject($this->ssoListener);
        $refMethod = $refListener->getMethod('getPreAuthenticatedData');
        $refMethod->setAccessible(true);
        $this->assertEquals([$finalUser, $passwordHash], $refMethod->invoke($this->ssoListener, new Request()));
    }
}
