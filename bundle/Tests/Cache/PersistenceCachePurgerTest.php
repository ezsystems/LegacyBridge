<?php
/**
 * File containing the PersistenceCachePurgeTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\Cache;

use eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger;
use eZ\Publish\SPI\Persistence\Content\Location;
use eZ\Publish\SPI\Persistence\Content\Location\Handler;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use PHPUnit\Framework\TestCase;

class PersistenceCachePurgerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $locationHandler;

    /**
     * @var \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger
     */
    private $cachePurger;

    protected function setUp()
    {
        parent::setUp();
        $this->cacheService = $this->createMock(TagAwareAdapterInterface::class);
        $this->locationHandler = $this->createMock(Handler::class);

        $this->cachePurger = new PersistenceCachePurger(
            $this->cacheService,
            $this->locationHandler
        );
    }

    /**
     * Test case for https://jira.ez.no/browse/EZP-20618.
     */
    public function testNotFoundLocation()
    {
        $id = 'locationIdThatDoesNotExist';
        $this->locationHandler
            ->expects($this->once())
            ->method('loadList')
            ->with([$id])
            ->willReturn([]);

        $this->cachePurger->content($id);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::all
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::isAllCleared
     */
    public function testClearAll()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('clear')
            ->with();

        $this->cachePurger->all();
        $this->assertTrue($this->cachePurger->isAllCleared());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::all
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::resetAllCleared
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::isAllCleared
     */
    public function testResetAllCleared()
    {
        $this->assertFalse($this->cachePurger->isAllCleared());
        $this->cachePurger->all();
        $this->assertTrue($this->cachePurger->isAllCleared());
        $this->cachePurger->resetAllCleared();
        $this->assertFalse($this->cachePurger->isAllCleared());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::all
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::content
     */
    public function testClearContentAlreadyCleared()
    {
        $this->cachePurger->all();
        $this->cacheService
            ->expects($this->never())
            ->method('clear');
        $this->cachePurger->content();
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::content
     */
    public function testClearContentDisabled()
    {
        $this->cachePurger->switchOff();
        $this->cacheService
            ->expects($this->never())
            ->method('clear');
        $this->cachePurger->content();
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::setEnabled
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::all
     */
    public function testClearAllDisabled()
    {
        $this->cachePurger->switchOff();
        $this->cacheService
            ->expects($this->never())
            ->method('clear');
        $this->cachePurger->all();
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::content
     */
    public function testClearAllContent()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('clear');

        $this->assertNull($this->cachePurger->content());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::content
     */
    public function testClearContent()
    {
        $locationId1 = 1;
        $contentId1 = 10;
        $locationId2 = 2;
        $contentId2 = 20;
        $locationId3 = 3;
        $contentId3 = 30;

        $locationIds = [$locationId1, $locationId2, $locationId3];

        $this->locationHandler
            ->expects($this->once())
            ->method('loadList')
            ->with($locationIds)
            ->willReturn([
                $locationId1 => $this->buildLocation($locationId1, $contentId1),
                $locationId2 => $this->buildLocation($locationId2, $contentId2),
                $locationId3 => $this->buildLocation($locationId3, $contentId3),
            ]);

        $this->cacheService
            ->expects($this->any())
            ->method('clear')
            ->will(
                $this->returnValueMap(
                    [
                        ['content', $contentId1, null],
                        ['content', 'info', $contentId1, null],
                        ['content', $contentId2, null],
                        ['content', 'info', $contentId2, null],
                        ['content', $contentId3, null],
                        ['content', 'info', $contentId3, null],
                        ['urlAlias', null],
                        ['location', null],
                    ]
                )
            );

        $this->assertSame($locationIds, $this->cachePurger->content($locationIds));
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::content
     */
    public function testClearOneContent()
    {
        $locationId = 1;
        $contentId = 10;

        $this->locationHandler
            ->expects($this->once())
            ->method('loadList')
            ->with([$locationId])
            ->willReturn([$locationId => $this->buildLocation($locationId, $contentId)]);

        $this->cacheService
            ->expects($this->any())
            ->method('clear')
            ->will(
                $this->returnValueMap(
                    [
                        ['content', $contentId, null],
                        ['content', 'info', $contentId, null],
                        ['content', 'info', 'remoteId', null],
                        ['urlAlias', null],
                        ['location', null],
                    ]
                )
            );

        $this->assertSame([$locationId], $this->cachePurger->content($locationId));
    }

    /**
     * @param $locationId
     * @param $contentId
     * @return \eZ\Publish\SPI\Persistence\Content\Location
     */
    private function buildLocation($locationId, $contentId)
    {
        return new Location(
            [
                'id' => $locationId,
                'contentId' => $contentId,
            ]
        );
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::content
     */
    public function testClearContentFail()
    {
        $this->expectException(\eZ\Publish\Core\Base\Exceptions\InvalidArgumentType::class);

        $this->cachePurger->content(new \stdClass());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::contentType
     */
    public function testClearContentTypeAll()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['type-map']);

        $this->cachePurger->contentType();
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::contentType
     */
    public function testClearContentType()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['type-123']);

        $this->cachePurger->contentType(123);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::contentType
     */
    public function testClearContentTypeFail()
    {
        $this->expectException(\eZ\Publish\Core\Base\Exceptions\InvalidArgumentType::class);

        $this->cachePurger->contentType(new \stdClass());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::contentTypeGroup
     */
    public function testClearContentTypeGroup()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['type-group-123', 'type-map']);

        $this->cachePurger->contentTypeGroup(123);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::contentTypeGroup
     */
    public function testClearContentTypeGroupFail()
    {
        $this->expectException(\eZ\Publish\Core\Base\Exceptions\InvalidArgumentType::class);

        $this->cachePurger->contentTypeGroup(new \stdClass());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::section
     */
    public function testClearSection()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['section-123']);

        $this->cachePurger->section(123);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::section
     */
    public function testClearSectionFail()
    {
        $this->expectException(\eZ\Publish\Core\Base\Exceptions\InvalidArgumentType::class);

        $this->cachePurger->section(new \stdClass());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::languages
     */
    public function testClearLanguages()
    {
        $languageId1 = 123;
        $languageId2 = 456;
        $languageId3 = 789;

        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['language-123', 'language-456', 'language-789']);

        $this->cachePurger->languages([$languageId1, $languageId2, $languageId3]);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::languages
     */
    public function testClearOneLanguage()
    {
        $languageId = 123;

        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['language-123']);

        $this->cachePurger->languages($languageId);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::user
     */
    public function testClearUserAll()
    {
        $this->markTestSkipped('Enable when clearing all user cache is implemented.');

        $this->cacheService
            ->expects($this->once())
            ->method('clear');

        $this->cachePurger->user();
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::user
     */
    public function testClearUser()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['user-123']);

        $this->cachePurger->user(123);
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::user
     */
    public function testClearUserFail()
    {
        $this->expectException(\eZ\Publish\Core\Base\Exceptions\InvalidArgumentType::class);

        $this->cachePurger->user(new \stdClass());
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger::contentVersion
     * @dataProvider getDataForTestClearVersionForOneContent
     */
    public function testClearVersionOfOneContent($contentId, $versionNo)
    {
        $this->cacheService
            ->expects($this->once())
            ->method('deleteItem')
            ->with("ez-content-version-info-${contentId}-${versionNo}");

        $this->cacheService
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(["content-${contentId}-version-list", "content-${contentId}-version-${versionNo}"]);

        $this->cachePurger->contentVersion($contentId, $versionNo);
    }

    public function getDataForTestClearVersionForOneContent()
    {
        return [
            [18, 37],
        ];
    }
}
