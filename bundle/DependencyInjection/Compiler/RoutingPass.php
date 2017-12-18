<?php
/**
 * This file is part of the eZ Publish Kernel package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishLegacyBundle\Routing\DefaultRouter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Pass modifying the default router to inject legacy aware routes.
 */
class RoutingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('router.default')) {
            return;
        }

        $container->getDefinition('router.default')
            ->setClass(DefaultRouter::class)
            ->addMethodCall(
                'setLegacyAwareRoutes',
                ['%ezpublish.default_router.legacy_aware_routes%']
            );

        if ($container->hasDefinition('ezpublish_rest.templated_router')) {
            $container->getDefinition('ezpublish_rest.templated_router')
                ->setClass(DefaultRouter::class);
        }
    }
}
