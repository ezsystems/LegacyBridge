<?php
/**
 * File containing the APIExceptionListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\EventListener;

use eZ\Publish\Core\MVC\Symfony\Event\APIContentExceptionEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\Exception\NotFound as ConverterNotFound;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\MVC\Legacy\View\Provider\Content as LegacyContentViewProvider;
use eZ\Publish\Core\MVC\Legacy\View\Provider\Location as LegacyLocationViewProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class APIContentExceptionListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\Legacy\View\Provider\Content
     */
    protected $legacyCVP;

    /**
     * @var \eZ\Publish\Core\MVC\Legacy\View\Provider\Location
     */
    protected $legacyLVP;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(LegacyContentViewProvider $legacyCVP, LegacyLocationViewProvider $legacyLVP, LoggerInterface $logger = null)
    {
        $this->legacyCVP = $legacyCVP;
        $this->legacyLVP = $legacyLVP;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::API_CONTENT_EXCEPTION => 'onAPIContentException',
        );
    }

    public function onAPIContentException(APIContentExceptionEvent $event)
    {
        $exception = $event->getApiException();
        $contentMeta = $event->getContentMeta();
        if ($exception instanceof ConverterNotFound) {
            if (isset($this->logger)) {
                $this->logger->notice(
                    'Missing field converter in legacy storage engine, forwarding to legacy kernel.',
                    array('content' => $contentMeta)
                );
            }

            $contentView = new ContentView();
            $contentView->setViewType($contentMeta['viewType']);

            if (isset($contentMeta['locationId'])) {
                $contentView->setLocation(new Location(array('id' => $contentMeta['locationId'])));
                $event->setContentView($this->legacyLVP->getView($contentView));
            } elseif (isset($contentMeta['contentId'])) {
                $contentView->setContent(
                    new Content(
                        array(
                            'versionInfo' => new VersionInfo(
                                array(
                                    'contentInfo' => new ContentInfo(array('id' => $contentMeta['contentId'])),
                                )
                            ),
                        )
                    )
                );
                $event->setContentView($this->legacyCVP->getView($contentView));
            }

            $event->stopPropagation();
        }
    }
}
