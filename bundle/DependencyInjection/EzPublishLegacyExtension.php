<?php
/**
 * File containing the EzPublishLegacyExtension class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

class EzPublishLegacyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('ezpublish_legacy.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');

        // Conditionally load HTTP cache services compatible with
        // ezsystems/ezplatform-http-cache based on availability of its bundle
        $activatedBundles = $container->getParameter('kernel.bundles');
        \array_key_exists('EzSystemsPlatformHttpCacheBundle', $activatedBundles) ?
            $loader->load('http_cache/services.yml') :
            $loader->load('http_cache/legacy_services.yml');

        // Security services
        $loader->load('security.yml');

        if (isset($config['root_dir'])) {
            $container->setParameter('ezpublish_legacy.root_dir', $config['root_dir']);
        }

        // Templating
        $loader->load('templating.yml');

        // View
        $loader->load('view.yml');

        // Fieldtype Services
        $loader->load('fieldtype_services.yml');

        // SignalSlot settings
        $loader->load('slot.yml');

        $loader->load('debug.yml');

        // Default settings
        $loader->load('default_settings.yml');

        $processor = new ConfigurationProcessor($container, 'ezpublish_legacy');
        $processor->mapConfig(
            $config,
            static function (array $scopeSettings, $currentScope, ContextualizerInterface $contextualizer) {
                if (isset($scopeSettings['templating']['view_layout'])) {
                    $contextualizer->setContextualParameter('view_default_layout', $currentScope, $scopeSettings['templating']['view_layout']);
                }

                if (isset($scopeSettings['templating']['module_layout'])) {
                    $contextualizer->setContextualParameter('module_default_layout', $currentScope, $scopeSettings['templating']['module_layout']);
                }

                if (isset($scopeSettings['not_found_http_conversion'])) {
                    $contextualizer->setContextualParameter('not_found_http_conversion', $currentScope, $scopeSettings['not_found_http_conversion']);
                }

                if (isset($scopeSettings['legacy_mode'])) {
                    $container = $contextualizer->getContainer();
                    $container->setParameter("ezsettings.$currentScope.legacy_mode", $scopeSettings['legacy_mode']);
                    $container->setParameter("ezsettings.$currentScope.url_alias_router", !$scopeSettings['legacy_mode']);
                }
            }
        );

        // Define additional routes that are allowed with legacy_mode: true.
        if (isset($config['legacy_aware_routes'])) {
            $container->setParameter(
                'ezpublish.default_router.legacy_aware_routes',
                array_merge(
                    $container->getParameter('ezpublish.default_router.legacy_aware_routes'),
                    $config['legacy_aware_routes']
                )
            );
        }
    }
}
