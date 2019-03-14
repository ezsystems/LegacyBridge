<?php
/**
 * File containing the EzPublishLegacyExtensionTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\EzPublishLegacyExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class EzPublishLegacyExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new EzPublishLegacyExtension(),
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        $this->setParameter('kernel.bundles', []);
    }

    public function testBundleNotEnabled()
    {
        $this->load(['enabled' => false]);
        $this->assertContainerBuilderHasParameter('ezpublish_legacy.enabled', false);
        $this->assertFalse($this->container->hasParameter('ezpublish_legacy.root_dir'));
        $this->assertContainerBuilderNotHasService('ezpublish_legacy.kernel.lazy');
        $this->assertFalse($this->container->hasAlias('ezpublish_legacy.kernel'));
    }

    public function testWrongRootDir()
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->load(
            [
                'enabled' => true,
                'root_dir' => '/some/inexistent/directory',
            ]
        );
    }

    public function testDefaultConfigValues()
    {
        $this->load(
            [
                'enabled' => true,
                'root_dir' => __DIR__,
            ]
        );
        $this->assertContainerBuilderHasParameter('ezpublish_legacy.enabled', true);
        $this->assertContainerBuilderHasParameter('ezpublish_legacy.root_dir', __DIR__);
        $this->assertContainerBuilderHasService('ezpublish_legacy.kernel.lazy');
        $this->assertContainerBuilderHasAlias('ezpublish_legacy.kernel', 'ezpublish_legacy.kernel.lazy');

        $this->assertContainerBuilderHasParameter(
            'ezpublish_legacy.default.view_default_layout',
            '@EzPublishLegacy/legacy_view_default_pagelayout.html.twig'
        );
        $this->assertContainerBuilderHasParameter('ezpublish_legacy.default.module_default_layout', null);
    }

    public function testViewLayout()
    {
        ConfigurationProcessor::setAvailableSiteAccesses(['sa1', 'sa2', 'sa3']);
        $groupsBySiteAccess = [
            'sa2' => ['sa_group'],
        ];
        ConfigurationProcessor::setGroupsBySiteAccess($groupsBySiteAccess);

        $layoutSa1 = 'view_layout_for_sa1.html.twig';
        $layoutSaGroup = 'view_layout_for_sa_group.html.twig';
        $defaultLayout = '@EzPublishLegacy/legacy_view_default_pagelayout.html.twig';
        $config = [
            'enabled' => true,
            'root_dir' => __DIR__,
            'system' => [
                'sa1' => [
                    'templating' => [
                        'view_layout' => $layoutSa1,
                    ],
                ],
                'sa_group' => [
                    'templating' => [
                        'view_layout' => $layoutSaGroup,
                    ],
                ],
            ],
        ];

        $this->load($config);
        $this->assertContainerBuilderHasParameter('ezpublish_legacy.default.view_default_layout', $defaultLayout);

        // Testing values with real ConfigResolver
        $configResolver = new ConfigResolver($groupsBySiteAccess, 'foo');
        $configResolver->setContainer($this->container);
        $this->assertSame($layoutSa1, $configResolver->getParameter('view_default_layout', 'ezpublish_legacy', 'sa1'));
        $this->assertSame($layoutSaGroup, $configResolver->getParameter('view_default_layout', 'ezpublish_legacy', 'sa2'));
        $this->assertSame($defaultLayout, $configResolver->getParameter('view_default_layout', 'ezpublish_legacy', 'sa3'));
    }

    public function testGlobalLayout()
    {
        ConfigurationProcessor::setAvailableSiteAccesses(['sa1', 'sa2', 'sa3']);
        $groupsBySiteAccess = [
            'sa2' => ['sa_group'],
        ];
        ConfigurationProcessor::setGroupsBySiteAccess($groupsBySiteAccess);

        $layoutSa1 = 'module_layout_for_sa1.html.twig';
        $layoutSaGroup = 'module_layout_for_sa_group.html.twig';
        $defaultLayout = null;
        $config = [
            'enabled' => true,
            'root_dir' => __DIR__,
            'system' => [
                'sa1' => [
                    'templating' => [
                        'module_layout' => $layoutSa1,
                    ],
                ],
                'sa_group' => [
                    'templating' => [
                        'module_layout' => $layoutSaGroup,
                    ],
                ],
            ],
        ];

        $this->load($config);
        $this->assertContainerBuilderHasParameter('ezpublish_legacy.default.module_default_layout', $defaultLayout);

        // Testing values with real ConfigResolver
        $configResolver = new ConfigResolver($groupsBySiteAccess, 'foo');
        $configResolver->setContainer($this->container);
        $this->assertSame($layoutSa1, $configResolver->getParameter('module_default_layout', 'ezpublish_legacy', 'sa1'));
        $this->assertSame($layoutSaGroup, $configResolver->getParameter('module_default_layout', 'ezpublish_legacy', 'sa2'));
        $this->assertSame($defaultLayout, $configResolver->getParameter('module_default_layout', 'ezpublish_legacy', 'sa3'));
    }
}
