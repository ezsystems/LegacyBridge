<?php
/**
 * This file is part of the eZ Publish Kernel package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishCoreBundle\Cache\Http\InstantCachePurger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Pass modifying the switchable_http_cache_purger when ezplatform.http_cache is available.
 */
class HttpCachePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('ezplatform.http_cache.purge_client')) {
            return;
        }

        $container->addDefinitions([
            'ezplatform.http_cache.purger' => new Definition(InstantCachePurger::class,
                [new Reference('ezplatform.http_cache.purge_client')]),
        ]);

        $container->getDefinition('ezpublish_legacy.switchable_http_cache_purger')
            ->replaceArgument(0, new Reference('ezplatform.http_cache.purger'));
    }
}
