<?php
/**
 * File containing the LegacyResponseManagerTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\LegacyResponse;

use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse\LegacyResponseManager;
use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ezpKernelResult;
use ezpKernelRedirect;
use DateTime;
use PHPUnit\Framework\TestCase;

class LegacyResponseManagerTest extends TestCase
{
    /**
     * @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateEngine;

    /**
     * @var ConfigResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configResolver;

    protected function setUp()
    {
        parent::setUp();
        $this->templateEngine = $this->createMock(EngineInterface::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
    }

    /**
     * @dataProvider generateResponseAccessDeniedProvider
     */
    public function testGenerateResponseAccessDenied($errorCode, $errorMessage)
    {
        $this->expectException(AccessDeniedException::class);
        if (null !== $errorMessage) {
            $this->expectExceptionMessage($errorMessage);
        }

        $manager = new LegacyResponseManager($this->templateEngine, $this->configResolver, new RequestStack());
        $content = 'foobar';
        $moduleResult = [
            'content' => $content,
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
        ];
        $kernelResult = new ezpKernelResult($content, ['module_result' => $moduleResult]);
        $manager->generateResponseFromModuleResult($kernelResult);
    }

    public function generateResponseAccessDeniedProvider()
    {
        return [
            ['401', 'Unauthorized access'],
            ['403', 'Forbidden'],
            ['403', null],
            ['401', null],
        ];
    }

    /**
     * @param bool $legacyMode whether legacy mode is active or not
     * @param bool $expectException whether exception is expected
     * @dataProvider generateResponseNotFoundProvider
     */
    public function testGenerateResponseNotFound($legacyMode, $expectException)
    {
        $this->configResolver
            ->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['legacy_mode', null, null, $legacyMode],
                    ]
                )
            );
        if ($expectException) {
            $this->expectException(NotFoundHttpException::class);
            $this->expectExceptionMessage('Not found');
        }
        $manager = new LegacyResponseManager($this->templateEngine, $this->configResolver, new RequestStack());
        $content = 'foobar';
        $moduleResult = [
            'content' => $content,
            'errorCode' => 404,
            'errorMessage' => 'Not found',
        ];
        $kernelResult = new ezpKernelResult($content, ['module_result' => $moduleResult]);
        $response = $manager->generateResponseFromModuleResult($kernelResult);
        if (!$expectException) {
            $this->assertSame($moduleResult['errorCode'], $response->getStatusCode());
        }
    }

    public function generateResponseNotFoundProvider()
    {
        return [
            [true, false],
            [false, true],
        ];
    }

    public function testLegacyResultHasLayout()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $manager = new LegacyResponseManager($this->templateEngine, $this->configResolver, $requestStack);
        self::assertFalse($manager->legacyResultHasLayout(new ezpKernelResult()));

        $resultWithLegacyLayout = new ezpKernelResult('', ['module_result' => ['pagelayout' => 'foo.tpl']]);
        self::assertTrue($manager->legacyResultHasLayout($resultWithLegacyLayout));

        $request = new Request();
        $request->attributes->set('_route', '_ezpublishLegacyLayoutSet');
        $requestStack->pop();
        $requestStack->push($request);
        self::assertTrue($manager->legacyResultHasLayout($resultWithLegacyLayout));
    }

    /**
     * Tests response generation when no custom layout can be applied:
     *  - Custom layout provided, but in legacy mode
     *  - Custom layout provided, module_result presents a "pagelayout" entry
     *  - Legacy mode active, no custom layout.
     *
     * @param string|null $customLayout custom Twig layout being used, or null if none
     * @param bool $legacyMode whether legacy mode is active or not
     * @param bool $moduleResultLayout whether if module_result from legacy contains a "pagelayout" entry
     * @param bool $isLayoutSetModule whether current request is using /layout/set/ route
     *
     * @dataProvider generateResponseNoCustomLayoutProvider
     */
    public function testGenerateResponseNoCustomLayout($customLayout, $legacyMode, $moduleResultLayout, $isLayoutSetModule)
    {
        $this->configResolver
            ->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['module_default_layout', 'ezpublish_legacy', null, $customLayout],
                        ['legacy_mode', null, null, $legacyMode],
                    ]
                )
            );
        $this->templateEngine
            ->expects($this->never())
            ->method('render');

        $requestStack = new RequestStack();
        $request = new Request();
        if ($isLayoutSetModule) {
            $request->attributes->set('_route', '_ezpublishLegacyLayoutSet');
        }
        $requestStack->push($request);

        $manager = new LegacyResponseManager($this->templateEngine, $this->configResolver, $requestStack);
        $content = 'foobar';
        $moduleResult = [
            'content' => $content,
            'errorCode' => 200,
        ];
        if ($moduleResultLayout) {
            $moduleResult['pagelayout'] = 'design:some_page_layout.tpl';
        }

        $kernelResult = new ezpKernelResult($content, ['module_result' => $moduleResult]);

        $response = $manager->generateResponseFromModuleResult($kernelResult);
        $this->assertInstanceOf(LegacyResponse::class, $response);
        $this->assertSame($content, $response->getContent());
        $this->assertSame($moduleResult['errorCode'], $response->getStatusCode());
    }

    public function generateResponseNoCustomLayoutProvider()
    {
        return [
            [null, false, false, false],
            ['foo.html.twig', true, false, false],
            ['foo.html.twig', false, true, false],
            [null, false, true, false],
            [null, true, true, false],
            [null, false, false, false, true],
        ];
    }

    /**
     * @dataProvider generateResponseWithCustomLayoutProvider
     */
    public function testGenerateResponseWithCustomLayout($customLayout, $content)
    {
        $contentWithLayout = "<div id=\"i-am-a-twig-layout\">$content</div>";
        $moduleResult = [
            'content' => $content,
            'errorCode' => 200,
        ];

        $this->configResolver
            ->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['module_default_layout', 'ezpublish_legacy', null, $customLayout],
                        ['legacy_mode', null, null, false],
                    ]
                )
            );
        $this->templateEngine
            ->expects($this->once())
            ->method('render')
            ->with($customLayout, ['module_result' => $moduleResult])
            ->will($this->returnValue($contentWithLayout));

        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $manager = new LegacyResponseManager($this->templateEngine, $this->configResolver, $requestStack);

        $kernelResult = new ezpKernelResult($content, ['module_result' => $moduleResult]);

        $response = $manager->generateResponseFromModuleResult($kernelResult);
        $this->assertInstanceOf(LegacyResponse::class, $response);
        $this->assertSame($contentWithLayout, $response->getContent());
        $this->assertSame($moduleResult['errorCode'], $response->getStatusCode());
        $this->assertSame($moduleResult, $response->getModuleResult());
    }

    public function generateResponseWithCustomLayoutProvider()
    {
        return [
            ['foo.html.twig', 'Hello world!'],
            ['foo.html.twig', 'שלום עולם!'],
            ['bar.html.twig', 'こんにちは、世界'],
            ['i_am_a_custom_layout.html.twig', 'Know what? I\'m a legacy content!'],
            ['custom.twig', 'I love content management.'],
            ['custom.twig', '私は、コンテンツ管理が大好きです。'],
            ['custom.twig', 'אני אוהב את ניהול תוכן.'],
        ];
    }

    /**
     * @dataProvider generateRedirectResponseProvider
     */
    public function testGenerateRedirectResponse($uri, $redirectStatus, $expectedStatusCode, $content)
    {
        $kernelRedirect = new ezpKernelRedirect($uri, $redirectStatus, $content);
        $manager = new LegacyResponseManager($this->templateEngine, $this->configResolver, new RequestStack());
        $response = $manager->generateRedirectResponse($kernelRedirect);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($uri, $response->getTargetUrl());
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
    }

    public function generateRedirectResponseProvider()
    {
        return [
            ['/foo', null, 302, null],
            ['/foo', '302', 302, 'bar'],
            ['/foo/bar', '301: blablabla', 301, 'Hello world!'],
            ['/foo/bar?some=thing&toto=titi', '303: See other', 303, 'こんにちは、世界!'],
        ];
    }

    public function testMapHeaders()
    {
        $etag = '86fb269d190d2c85f6e0468ceca42a20';
        $date = new DateTime();
        $dateForCache = $date->format('D, d M Y H:i:s') . ' GMT';
        $headers = ['X-Foo: Bar', "Etag: $etag", "Last-Modified: $dateForCache", "Expires: $dateForCache"];

        // Partially mock the manager to simulate calls to header_remove()
        $manager = $this->getMockBuilder(LegacyResponseManager::class)
            ->setConstructorArgs([$this->templateEngine, $this->configResolver, new RequestStack()])
            ->setMethods(['removeHeader'])
            ->getMock();
        $manager
            ->expects($this->exactly(\count($headers)))
            ->method('removeHeader');
        /** @var \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse\LegacyResponseManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $response = new LegacyResponse();
        $responseMappedHeaders = $manager->mapHeaders($headers, $response);
        $this->assertSame(spl_object_hash($response), spl_object_hash($responseMappedHeaders));
        $this->assertSame('Bar', $responseMappedHeaders->headers->get('X-Foo'));
        $this->assertSame('"' . $etag . '"', $responseMappedHeaders->getEtag());
        $this->assertEquals(new DateTime($dateForCache), $responseMappedHeaders->getLastModified());
        $this->assertEquals(new DateTime($dateForCache), $responseMappedHeaders->getExpires());
    }

    public function testEmptyHeaderValueShouldNotRaiseNotice()
    {
        $manager = $this->getMockBuilder(LegacyResponseManager::class)
            ->setConstructorArgs([$this->templateEngine, $this->configResolver, new RequestStack()])
            ->setMethods(['removeHeader'])
            ->getMock();
        /** @var \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse\LegacyResponseManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $headers = ['X-Foo: Bar', 'Pragma:'];
        $response = new LegacyResponse();

        $manager->mapHeaders($headers, $response);
    }
}
