<?php
/**
 * File containing the DefinitionBasedAdapterTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Tests\Adapter;

use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\MVC\Legacy\Templating\Adapter\DefinitionBasedAdapter;

class DefinitionBasedAdapterTest extends ValueObjectAdapterTest
{
    protected function getAdapter(ValueObject $valueObject, array $map)
    {
        $adapter = $this
            ->getMockBuilder(DefinitionBasedAdapter::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $adapter
            ->expects($this->once())
            ->method('definition')
            ->will($this->returnValue($map));
        $adapter->__construct($valueObject);

        return $adapter;
    }
}
