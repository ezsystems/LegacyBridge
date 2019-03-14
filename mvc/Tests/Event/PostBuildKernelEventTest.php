<?php
/**
 * File containing the PostBuildKernelEventTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Tests\Event;

use eZ\Publish\Core\MVC\Legacy\Event\PostBuildKernelEvent;
use eZ\Publish\Core\MVC\Legacy\Kernel;
use PHPUnit\Framework\TestCase;
use ezpKernelHandler;

class PostBuildKernelEventTest extends TestCase
{
    public function testConstruct()
    {
        $kernelHandler = $this->createMock(ezpKernelHandler::class);
        $legacyKernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$kernelHandler, 'foo', 'bar'])
            ->getMock();
        $event = new PostBuildKernelEvent($legacyKernel, $kernelHandler);
        $this->assertSame($legacyKernel, $event->getLegacyKernel());
        $this->assertSame($kernelHandler, $event->getKernelHandler());
    }
}
