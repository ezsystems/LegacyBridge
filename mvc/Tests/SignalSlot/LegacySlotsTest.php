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
use PHPUnit_Framework_TestCase;

/**
 * @group signalSlot
 */
class LegacySlotsTest extends PHPUnit_Framework_TestCase
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
        $this->ezpKernelHandlerMock = $this->getMock('\ezpKernelHandler');

        $this->persistenceCachePurgerMock = $this->getMockForTrait('eZ\Bundle\EzPublishLegacyBundle\Cache\Switchable');
        $this->httpCachePurgerMock = $this->getMockForTrait('eZ\Bundle\EzPublishLegacyBundle\Cache\Switchable');

        parent::setUp();
    }

    /**
     * @covers \eZ\Publish\Core\SignalSlot\Slot\AbstractLegacySlot::getLegacyKernel
     */
    public function testAbstractLegacySlot()
    {
        $ezpKernelHandlerMock = $this->ezpKernelHandlerMock;

        $legacySlotMock = $this->getMock(
            'eZ\Publish\Core\MVC\Legacy\SignalSlot\AbstractLegacySlot',
            // methods
            array(),
            // ctor arguments
            array(
                $ezpKernelHandlerMock,
                $this->persistenceCachePurgerMock,
                $this->httpCachePurgerMock,
            )
        );

        $reflectionProperty = new \ReflectionProperty('eZ\Publish\Core\MVC\Legacy\SignalSlot\AbstractLegacySlot', 'legacyKernel');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($ezpKernelHandlerMock, $reflectionProperty->getValue($legacySlotMock));
    }

    public function providerForTestLegacySlots()
    {
        return array(
            array('LegacyAssignSectionSlot', 'SectionService\\AssignSectionSignal', array()),
            array('LegacyCopyContentSlot', 'ContentService\\CopyContentSignal', array()),
            array('LegacyCreateLocationSlot', 'LocationService\\CreateLocationSignal', array()),
            array('LegacyDeleteContentSlot', 'ContentService\\DeleteContentSignal', array()),
            array('LegacyDeleteLocationSlot', 'LocationService\\DeleteLocationSignal', array()),
            array('LegacyDeleteVersionSlot', 'ContentService\\DeleteVersionSignal', array()),
            array('LegacyHideLocationSlot', 'LocationService\\HideLocationSignal', array()),
            array('LegacyMoveSubtreeSlot', 'LocationService\\MoveSubtreeSignal', array()),
            array('LegacyPublishVersionSlot', 'ContentService\\PublishVersionSignal', array()),
            array('LegacySetContentStateSlot', 'ObjectStateService\\SetContentStateSignal', array()),
            array('LegacySwapLocationSlot', 'LocationService\\SwapLocationSignal', array()),
            array('LegacyUnhideLocationSlot', 'LocationService\\UnhideLocationSignal', array()),
            array('LegacyUpdateLocationSlot', 'LocationService\\UpdateLocationSignal', array()),
            array('LegacyPublishContentTypeDraftSlot', 'ContentTypeService\\PublishContentTypeDraftSignal', array()),
        );
    }

    /**
     * @dataProvider providerForTestLegacySlots
     */
    public function testLegacySlotsValidSignal($slotName, $signalName, array $signalProperties = array())
    {
        $ezpKernelHandlerMock = $this->ezpKernelHandlerMock;
        $signalClassName = self::SIGNAL_SLOT_NS . '\\Signal\\' . $signalName;
        $slotClassName = self::LEGACY_SIGNAL_SLOT_NS . '\\' . $slotName;

        /**
         * @var \eZ\Publish\Core\SignalSlot\Slot
         */
        $slot = new $slotClassName(
            function () use ($ezpKernelHandlerMock) {
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
            function () use ($ezpKernelHandlerMock) {
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
        $signal = $this->getMock(self::SIGNAL_SLOT_NS . '\\Signal');
        $slot->receive($signal);
    }
}
