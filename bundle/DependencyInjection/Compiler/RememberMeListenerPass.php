<?php
/**
 * This file is part of the eZ LegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishLegacyBundle\Security\RememberMeListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RememberMeListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('security.authentication.listener.rememberme')) {
            return;
        }

        $container->findDefinition('security.authentication.listener.rememberme')
            ->setClass(RememberMeListener::class)
            ->addMethodCall('setConfigResolver', [new Reference('ezpublish.config.resolver')]);
    }
}
