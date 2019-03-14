<?php
/**
 * File containing the PageServiceTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\FieldType\Page;

use eZ\Publish\Core\FieldType\Tests\Page\PageServiceTest as BaseTest;
use eZ\Bundle\EzPublishLegacyBundle\FieldType\Page\PageService;

class PageServiceTest extends BaseTest
{
    const PAGESERVICE_CLASS = PageService::class;

    protected function getZoneDefinition()
    {
        return parent::getZoneDefinition() + [
            'layoutLegacy1' => [
                'zoneTypeName' => 'Layout legacy 1',
                'zones' => [
                    'main' => ['name' => 'Global zone'],
                ],
                'zoneThumbnail' => 'globalzone_layout.gif',
                'template' => 'legacytemplate.tpl',
                'availableForClasses' => ['frontpage'],
            ],
            'layoutLegacy2' => [
                'zoneTypeName' => 'Layout legacy 2',
                'zones' => [
                    'main' => ['name' => 'Global zone'],
                ],
                'zoneThumbnail' => 'globalzone_layout.gif',
                'template' => 'design:foo/legacytemplate.tpl',
                'availableForClasses' => ['frontpage'],
            ],
        ];
    }

    public function getLayoutTemplateProvider()
    {
        return parent::getLayoutTemplateProvider() + [
            ['layoutLegacy1', 'design:zone/legacytemplate.tpl'],
            ['layoutLegacy2', 'design:zone/foo/legacytemplate.tpl'],
        ];
    }
}
