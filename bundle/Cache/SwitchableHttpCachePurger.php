<?php
/**
 * This file is part of the EzPublishLegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Cache;

use eZ\Publish\Core\MVC\Symfony\Cache\GatewayCachePurger;
use eZ\Publish\API\Repository\LocationService;

/**
 * A GatewayCachePurger decorator that allows the actual purger to be switched on/off.
 */
class SwitchableHttpCachePurger implements GatewayCachePurger
{
    use Switchable;

    /** @var \eZ\Publish\Core\MVC\Symfony\Cache\GatewayCachePurger */
    private $gatewayCachePurger;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    public function __construct(GatewayCachePurger $gatewayCachePurger, LocationService $locationService)
    {
        $this->gatewayCachePurger = $gatewayCachePurger;
        $this->locationService = $locationService;
    }

    public function purge($cacheElements)
    {
        if ($this->isSwitchedOff()) {
            return $cacheElements;
        }

        foreach ($cacheElements as $locationId) {
            $location = $this->locationService->loadLocation($locationId);
            if ($locationId != 1) {
                $this->purgeForContent($location->contentId);
            }
        }

        return $cacheElements;
    }

    public function purgeAll()
    {
        if ($this->isSwitchedOff()) {
            return;
        }

        $this->gatewayCachePurger->purgeAll();
    }

    public function purgeForContent($contentId)
    {
        if ($this->isSwitchedOff()) {
            return;
        }

        $this->gatewayCachePurger->purgeForContent($contentId);
    }
}
