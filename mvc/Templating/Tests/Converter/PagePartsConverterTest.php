<?php
/**
 * File containing the PagePartsConverterTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Tests\Converter;

use eZ\Publish\Core\MVC\Legacy\Templating\Converter\PagePartsConverter;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\MVC\Legacy\Templating\Adapter\BlockAdapter;
use eZ\Publish\Core\FieldType\Page\Parts\Block;
use eZ\Publish\Core\FieldType\Page\Parts\Zone;
use eZ\Publish\Core\MVC\Legacy\Templating\Adapter\ZoneAdapter;
use PHPUnit\Framework\TestCase;

class PagePartsConverterTest extends TestCase
{
    /**
     * @dataProvider convertProvider
     * @covers \eZ\Publish\Core\MVC\Legacy\Templating\Converter\PagePartsConverter::convert
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $valueObject
     * @param $expectedAdapterClass
     */
    public function testConvert(ValueObject $valueObject, $expectedAdapterClass)
    {
        $converter = new PagePartsConverter();
        $this->assertInstanceOf($expectedAdapterClass, $converter->convert($valueObject));
    }

    public function convertProvider()
    {
        return [
            [
                $this->createMock(Block::class),
                BlockAdapter::class,
            ],
            [
                $this->createMock(Zone::class),
                ZoneAdapter::class,
            ],
        ];
    }

    /**
     * @dataProvider convertFailNotObjectProvider
     * @covers \eZ\Publish\Core\MVC\Legacy\Templating\Converter\PagePartsConverter::convert
     */
    public function testConvertFailNotObject($value)
    {
        $this->expectException(\InvalidArgumentException::class);

        $converter = new PagePartsConverter();
        $converter->convert($value);
    }

    public function convertFailNotObjectProvider()
    {
        return [
            ['foo'],
            ['bar'],
            [123],
            [true],
            [[]],
        ];
    }

    /**
     * @covers \eZ\Publish\Core\MVC\Legacy\Templating\Converter\PagePartsConverter::convert
     */
    public function testConvertFailWrongType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $converter = new PagePartsConverter();
        $converter->convert($this->createMock(ValueObject::class));
    }
}
