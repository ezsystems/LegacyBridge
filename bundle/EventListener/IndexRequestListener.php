<?php
/**
 * File containing the IndexRequestListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\EventListener;

use eZ\Bundle\EzPublishCoreBundle\EventListener\IndexRequestListener as CoreIndexListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class IndexRequestListener extends CoreIndexListener
{
    /**
     * Overrides core index request, which checks if the IndexPage is configured and which page must be shown.
     * If matched SiteAccess uses legacy mode, do not execute event.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestIndex(GetResponseEvent $event)
    {
        if ($this->configResolver->getParameter('legacy_mode')) {
            return;
        }
        parent::onKernelRequestIndex($event);
    }
}
