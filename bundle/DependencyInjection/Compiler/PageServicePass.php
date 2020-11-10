<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishLegacyBundle\FieldType\Page\PageService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PageServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ezpublish.fieldType.ezpage.pageService')) {
            return;
        }

        $container->findDefinition('ezpublish.fieldType.ezpage.pageService')
            ->setClass(PageService::class);
    }
}
