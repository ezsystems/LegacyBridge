<?php
/**
 * This file is part of the eZ Publish Kernel package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\Cache;

use eZ\Bundle\EzPublishLegacyBundle\Cache\SwitchableHttpCachePurger;
use EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface;
use PHPUnit\Framework\TestCase;

class SwitchableHttpCachePurgerTest extends TestCase
{
    /** @var \EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $purgeClientMockMock;

    /** @var \eZ\Bundle\EzPublishLegacyBundle\Cache\SwitchableHttpCachePurger */
    private $httpCachePurger;

    public function setUp()
    {
        $this->purgeClientMockMock = $this->createMock(PurgeClientInterface::class);
        $this->httpCachePurger = new SwitchableHttpCachePurger($this->purgeClientMockMock);
    }

    public function testPurgeSwitchedOn()
    {
        $this->httpCachePurger->switchOn();

        $this->purgeClientMockMock->expects($this->once())->method('purge')->willReturn($this->getCacheElements());
        self::assertEquals(
            $this->getCacheElements(),
            $this->httpCachePurger->purge($this->getCacheElements())
        );
    }

    public function testPurgeSwitchedOff()
    {
        $this->httpCachePurger->switchOff();
        $this->purgeClientMockMock->expects($this->never())->method('purge');
        self::assertEquals(
            $this->getCacheElements(),
            $this->httpCachePurger->purge($this->getCacheElements())
        );
    }

    public function testPurgeAllSwitchedOn()
    {
        $this->httpCachePurger->switchOn();
        $this->purgeClientMockMock->expects($this->once())->method('purgeAll');
        $this->httpCachePurger->purgeAll();
    }

    public function testPurgeAllSwitchedOff()
    {
        $this->httpCachePurger->switchOff();
        $this->purgeClientMockMock->expects($this->never())->method('purgeAll');
        $this->httpCachePurger->purgeAll();
    }

    private function getCacheElements()
    {
        return [1, 2, 3];
    }
}
