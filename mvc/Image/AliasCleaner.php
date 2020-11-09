<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\MVC\Legacy\Image;

use eZ\Publish\Core\FieldType\Image\AliasCleanerInterface;
use eZ\Publish\Core\IO\IOServiceInterface;
use eZ\Publish\Core\IO\UrlRedecoratorInterface;

class AliasCleaner implements AliasCleanerInterface
{
    /**
     * @var AliasCleanerInterface
     */
    private $innerAliasCleaner;

    /**
     * @var UrlRedecoratorInterface
     */
    private $urlRedecorator;

    /**
     * @var IOServiceInterface
     */
    private $IOService;

    public function __construct(
        AliasCleanerInterface $innerAliasCleaner,
        UrlRedecoratorInterface $urlRedecorator,
        IOServiceInterface $IOService
    ) {
        $this->innerAliasCleaner = $innerAliasCleaner;
        $this->urlRedecorator = $urlRedecorator;
        $this->IOService = $IOService;
    }

    public function removeAliases($originalPath)
    {
        $uri = $this->urlRedecorator->redecorateFromTarget($originalPath);
        $binaryFile = $this->IOService->loadBinaryFileByUri($uri);
        $this->innerAliasCleaner->removeAliases($binaryFile->id);
    }
}
