<?php
/**
 * File containing the EzpModuleViewNotFoundToNotFoundListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\EventListener;

use ezpModuleViewNotFound;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class EzpModuleViewNotFoundToNotFoundListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 512],
        ];
    }

    public function onException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof ezpModuleViewNotFound) {
            $event->setException(new NotFoundHttpException('Page Not Found', $event->getException()));
        }
    }
}
