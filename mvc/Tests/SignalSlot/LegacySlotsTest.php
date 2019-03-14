<?php
/**
 * File containing a test class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Tests\SignalSlot;

use eZ\Publish\Core\SignalSlot;
use eZ\Publish\Core\MVC\Legacy\SignalSlot\AbstractLegacySlot;
use eZ\Bundle\EzPublishLegacyBundle\Cache\Switchable;
use PHPUnit\Framework\TestCase;
use ezpKernelHandler;

/**
 * @group signalSlot
 */
class LegacySlotsTest extends TestCase
{
    const SIGNAL_SLOT_NS = 'eZ\Publish\Core\SignalSlot';
    const LEGACY_SIGNAL_SLOT_NS = 'eZ\Publish\Core\MVC\Legacy\SignalSlot';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ezpKernelHandlerMock;

    /** @var \eZ\Bundle\EzPublishLegacyBundle\Cache\Switchable|\PHPUnit_Framework_MockObject_MockObject */
    private $persistenceCachePurgerMock;

    /** @var \eZ\Bundle\EzPublishLegacyBundle\Cache\Switchable|\PHPUnit_Framework_MockObject_MockObject */
    private $httpCachePurgerMock;

    public function setUp()
    {
        $this->ezpKernelHandlerMock = $this->createMock(ezpKernelHandler::class);

        $this->persistenceCachePurgerMock = $this->getMockForTrait(Switchable::class);
        $this->httpCachePurgerMock = $this->getMockForTrait(Switchable::class);

        parent::setUp();
    }

    /**
     * @covers \eZ\Publish\Core\SignalSlot\Slot\AbstractLegacySlot::getLegacyKernel
     */
    public function testAbstractLegacySlot()
    {
        $ezpKernelHandlerMock = $this->ezpKernelHandlerMock;

        $legacySlotMock = $this->getMockBuilder(AbstractLegacySlot::class)
            ->setConstructorArgs([
                $ezpKernelHandlerMock,
                $this->persistenceCachePurgerMock,
                $this->httpCachePurgerMock,
            ])
            ->setMethods([])
            ->getMock();

        $reflectionProperty = new \ReflectionProperty(AbstractLegacySlot::class, 'legacyKernel');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($ezpKernelHandlerMock, $reflectionProperty->getValue($legacySlotMock));
    }

    public function providerForTestLegacySlots()
    {
        return [
            ['LegacyAssignSectionSlot', 'SectionService\\AssignSectionSignal', []],
            ['LegacyCopyContentSlot', 'ContentService\\CopyContentSignal', []],
            ['LegacyCreateLocationSlot', 'LocationService\\CreateLocationSignal', []],
            ['LegacyDeleteContentSlot', 'ContentService\\DeleteContentSignal', []],
            ['LegacyDeleteLocationSlot', 'LocationService\\DeleteLocationSignal', []],
            ['LegacyDeleteVersionSlot', 'ContentService\\DeleteVersionSignal', []],
            ['LegacyHideLocationSlot', 'LocationService\\HideLocationSignal', []],
            ['LegacyMoveSubtreeSlot', 'LocationService\\MoveSubtreeSignal', []],
            ['LegacyPublishVersionSlot', 'ContentService\\PublishVersionSignal', []],
            ['LegacySetContentStateSlot', 'ObjectStateService\\SetContentStateSignal', []],
            ['LegacySwapLocationSlot', 'LocationService\\SwapLocationSignal', []],
            ['LegacyUnhideLocationSlot', 'LocationService\\UnhideLocationSignal', []],
            ['LegacyUpdateLocationSlot', 'LocationService\\UpdateLocationSignal', []],
            ['LegacyPublishContentTypeDraftSlot', 'ContentTypeService\\PublishContentTypeDraftSignal', []],
        ];
    }

    /**
     * @dataProvider providerForTestLegacySlots
     */
    public function testLegacySlotsValidSignal($slotName, $signalName, array $signalProperties = [])
    {
        $ezpKernelHandlerMock = $this->ezpKernelHandlerMock;
        $signalClassName = self::SIGNAL_SLOT_NS . '\\Signal\\' . $signalName;
        $slotClassName = self::LEGACY_SIGNAL_SLOT_NS . '\\' . $slotName;

        /**
         * @var \eZ\Publish\Core\SignalSlot\Slot
         */
        $slot = new $slotClassName(
            static function () use ($ezpKernelHandlerMock) {
                return $ezpKernelHandlerMock;
            },
            $this->persistenceCachePurgerMock,
            $this->httpCachePurgerMock
        );

        $ezpKernelHandlerMock
            ->expects($this->once())
            ->method('runCallback')
            ->will($this->returnValue(null));

        /**
         * @var \eZ\Publish\Core\SignalSlot\Signal
         */
        $signal = new $signalClassName($signalProperties);
        $slot->receive($signal);
    }

    /**
     * @dataProvider providerForTestLegacySlots
     */
    public function testLegacySlotsInValidSignal($slotName)
    {
        $ezpKernelHandlerMock = $this->ezpKernelHandlerMock;
        $slotClassName = self::LEGACY_SIGNAL_SLOT_NS . '\\' . $slotName;

        /**
         * @var \eZ\Publish\Core\SignalSlot\Slot
         */
        $slot = new $slotClassName(
            static function () use ($ezpKernelHandlerMock) {
                return $ezpKernelHandlerMock;
            },
            $this->persistenceCachePurgerMock,
            $this->httpCachePurgerMock
        );

        $ezpKernelHandlerMock
            ->expects($this->never())
            ->method('runCallback')
            ->will($this->returnValue(null));

        /**
         * @var \eZ\Publish\Core\SignalSlot\Signal
         */
        $signal = $this->createMock(self::SIGNAL_SLOT_NS . '\\Signal');
        $slot->receive($signal);
    }
}
