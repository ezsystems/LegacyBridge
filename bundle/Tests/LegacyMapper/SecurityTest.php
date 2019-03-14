<?php
/**
 * File containing the SecurityTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\LegacyMapper;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Legacy\Event\PostBuildKernelEvent;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelWebHandlerEvent;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Kernel;
use eZ\Publish\Core\Repository\Values\User\User;
use eZ\Bundle\EzPublishLegacyBundle\LegacyMapper\Security;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use PHPUnit\Framework\TestCase;
use ezpKernelHandler;
use ezpWebBasedKernelHandler;

class SecurityTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\API\Repository\Repository
     */
    private $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $authChecker;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = $this->createMock(Repository::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                LegacyEvents::POST_BUILD_LEGACY_KERNEL => 'onKernelBuilt',
                LegacyEvents::PRE_BUILD_LEGACY_KERNEL_WEB => 'onLegacyKernelWebBuild',
            ],
            Security::getSubscribedEvents()
        );
    }

    public function testOnKernelBuiltNotWebBasedHandler()
    {
        $kernelHandler = $this->createMock(ezpKernelHandler::class);
        $legacyKernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$kernelHandler, 'foo', 'bar'])
            ->getMock();
        $event = new PostBuildKernelEvent($legacyKernel, $kernelHandler);

        $this->repository
            ->expects($this->never())
            ->method('getCurrentUser');
        $legacyKernel
            ->expects($this->never())
            ->method('runCallback');

        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->onKernelBuilt($event);
    }

    public function testOnKernelBuiltWithLegacyMode()
    {
        $kernelHandler = $this->createMock(ezpWebBasedKernelHandler::class);
        $legacyKernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$kernelHandler, 'foo', 'bar'])
            ->getMock();
        $event = new PostBuildKernelEvent($legacyKernel, $kernelHandler);

        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(true));
        $this->repository
            ->expects($this->never())
            ->method('getCurrentUser');
        $legacyKernel
            ->expects($this->never())
            ->method('runCallback');

        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->onKernelBuilt($event);
    }

    public function testOnKernelBuiltDisabled()
    {
        $kernelHandler = $this->createMock(ezpWebBasedKernelHandler::class);
        $legacyKernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$kernelHandler, 'foo', 'bar'])
            ->getMock();
        $event = new PostBuildKernelEvent($legacyKernel, $kernelHandler);

        $this->repository
            ->expects($this->never())
            ->method('getCurrentUser');
        $legacyKernel
            ->expects($this->never())
            ->method('runCallback');

        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->setEnabled(false);
        $listener->onKernelBuilt($event);
    }

    public function testOnKerneBuiltNotAuthenticated()
    {
        $kernelHandler = $this->createMock(ezpWebBasedKernelHandler::class);
        $legacyKernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$kernelHandler, 'foo', 'bar'])
            ->getMock();
        $event = new PostBuildKernelEvent($legacyKernel, $kernelHandler);

        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will(
                $this->returnValue(
                    $this->createMock(TokenInterface::class)
                )
            );
        $this->authChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->will($this->returnValue(false));
        $this->repository
            ->expects($this->never())
            ->method('getCurrentUser');
        $legacyKernel
            ->expects($this->never())
            ->method('runCallback');

        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->onKernelBuilt($event);
    }

    public function testOnKernelBuilt()
    {
        $kernelHandler = $this->createMock(ezpWebBasedKernelHandler::class);
        $legacyKernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$kernelHandler, 'foo', 'bar'])
            ->getMock();
        $event = new PostBuildKernelEvent($legacyKernel, $kernelHandler);

        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will(
                $this->returnValue(
                    $this->createMock(TokenInterface::class)
                )
            );
        $this->authChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->will($this->returnValue(true));

        $userId = 123;
        $user = $this->generateUser($userId);
        $this->repository
            ->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $legacyKernel
            ->expects($this->once())
            ->method('runCallback');

        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->onKernelBuilt($event);
    }

    /**
     * @param $userId
     *
     * @return \eZ\Publish\Core\Repository\Values\User\User
     */
    private function generateUser($userId)
    {
        $versionInfo = $this->getMockForAbstractClass(VersionInfo::class);
        $versionInfo
            ->expects($this->any())
            ->method('getContentInfo')
            ->will($this->returnValue(new ContentInfo(['id' => $userId])));
        $content = $this->getMockForAbstractClass(Content::class);
        $content
            ->expects($this->any())
            ->method('getVersionInfo')
            ->will($this->returnValue($versionInfo));

        return new User(['content' => $content]);
    }

    public function testOnLegacyKernelWebBuildLegacyMode()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(true));

        $parameters = ['foo' => 'bar'];
        $event = new PreBuildKernelWebHandlerEvent(new ParameterBag($parameters), new Request());
        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->onLegacyKernelWebBuild($event);
        $this->assertSame($parameters, $event->getParameters()->all());
    }

    /**
     * @dataProvider onLegacyKernelWebBuildProvider
     */
    public function testOnLegacyKernelWebBuild(array $previousSettings, array $expected)
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with('legacy_mode')
            ->will($this->returnValue(false));

        $event = new PreBuildKernelWebHandlerEvent(new ParameterBag($previousSettings), new Request());
        $listener = new Security($this->repository, $this->configResolver, $this->tokenStorage, $this->authChecker);
        $listener->onLegacyKernelWebBuild($event);
        $this->assertSame($expected, $event->getParameters()->all());
    }

    public function onLegacyKernelWebBuildProvider()
    {
        return [
            [
                [],
                [
                    'injected-merge-settings' => [
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;user/login',
                            'module;user/logout',
                        ],
                    ],
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                ],
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;user/login',
                            'module;user/logout',
                        ],
                    ],
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'Empire' => ['Darth Vader', 'Emperor', 'Moff Tarkin'],
                        'Rebellion' => ['Luke Skywalker', 'Leïa Organa', 'Obi-Wan Kenobi', 'Han Solo'],
                        'Chewbacca' => 'Arrrrrhhhhhh!',
                    ],
                ],
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'Empire' => ['Darth Vader', 'Emperor', 'Moff Tarkin'],
                        'Rebellion' => ['Luke Skywalker', 'Leïa Organa', 'Obi-Wan Kenobi', 'Han Solo'],
                        'Chewbacca' => 'Arrrrrhhhhhh!',
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;user/login',
                            'module;user/logout',
                        ],
                    ],
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;ezinfo/about',
                            'access;enable',
                            'module;foo',
                        ],
                    ],
                ],
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;ezinfo/about',
                            'access;enable',
                            'module;foo',
                            'access;disable',
                            'module;user/login',
                            'module;user/logout',
                        ],
                    ],
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;ezinfo/about',
                            'access;enable',
                            'module;foo',
                        ],
                    ],
                ],
                [
                    'foo' => 'bar',
                    'some' => ['thing'],
                    'injected-merge-settings' => [
                        'site.ini/SiteAccessRules/Rules' => [
                            'access;disable',
                            'module;ezinfo/about',
                            'access;enable',
                            'module;foo',
                            'access;disable',
                            'module;user/login',
                            'module;user/logout',
                        ],
                    ],
                ],
            ],
        ];
    }
}
