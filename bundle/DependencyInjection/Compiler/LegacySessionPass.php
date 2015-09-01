<?php
/**
 * This file is part of the eZ Publish LegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Proxies configured session storage and session save handler services.
 */
class LegacySessionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('session.storage')) {
            return;
        }

        $sessionStorageAlias = $container->getAlias('session.storage');
        $sessionStorageProxyDef = $container->findDefinition('ezpublish_legacy.session_storage_proxy');
        $sessionStorageProxyDef->replaceArgument(1, new Reference((string)$sessionStorageAlias));
        $container->setAlias('session.storage', 'ezpublish_legacy.session_storage_proxy');

        if ($container->hasAlias('session.handler')) {
            $sessionHandlerAlias = $container->getAlias('session.handler');
            $interfaces = class_implements($container->findDefinition((string)$sessionHandlerAlias));
            // Only swap session handler if it implements appropriate interface.
            if (isset($interfaces['SessionHandlerInterface'])) {
                $sessionHandlerProxyDef = $container->findDefinition('ezpublish_legacy.session_handler_proxy');
                $sessionHandlerProxyDef->replaceArgument(1, new Reference((string)$sessionHandlerAlias));
                $container->setAlias('session.handler', 'ezpublish_legacy.session_handler_proxy');
            }
        }
    }
}
