<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\MVC\Legacy\Tests\Image;

use eZ\Publish\Core\IO\IOServiceInterface;
use eZ\Publish\Core\IO\Values\BinaryFile;
use eZ\Publish\Core\MVC\Legacy\Image\AliasCleaner;
use eZ\Publish\Core\FieldType\Image\AliasCleanerInterface;
use eZ\Publish\Core\IO\UrlRedecoratorInterface;
use PHPUnit\Framework\TestCase;

class AliasCleanerTest extends TestCase
{
    /**
     * @var \eZ\Publish\Core\MVC\Legacy\Image\AliasCleaner
     */
    private $aliasCleaner;

    /**
     * @var \eZ\Publish\Core\FieldType\Image\AliasCleanerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $innerAliasCleaner;

    /**
     * @var \eZ\Publish\Core\IO\UrlRedecoratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlRedecorator;

    /**
     * @var \eZ\Publish\Core\IO\IOServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $IOService;

    protected function setUp()
    {
        parent::setUp();
        $this->innerAliasCleaner = $this->createMock(AliasCleanerInterface::class);
        $this->urlRedecorator = $this->createMock(UrlRedecoratorInterface::class);
        $this->IOService = $this->createMock(IOServiceInterface::class);
        $this->aliasCleaner = new AliasCleaner($this->innerAliasCleaner, $this->urlRedecorator, $this->IOService);
    }

    public function testRemoveAliases()
    {
        $originalPath = 'var/storage/image/foo/bar/test.jpg';
        $uri = '/var/storage/image/foo/bar/test.jpg';
        $binaryFile = new BinaryFile(['id' => 'foo/bar/test.jpg']);

        $this->urlRedecorator
            ->expects($this->once())
            ->method('redecorateFromTarget')
            ->with($originalPath)
            ->willReturn($uri);

        $this->IOService
            ->expects($this->once())
            ->method('loadBinaryFileByUri')
            ->willReturn($binaryFile);

        $this->innerAliasCleaner
            ->expects($this->once())
            ->method('removeAliases');

        $this->aliasCleaner->removeAliases($originalPath);
    }
}
