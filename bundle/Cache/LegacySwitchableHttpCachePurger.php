<?php
/**
 * This file is part of the EzPublishLegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Cache;

use eZ\Publish\Core\MVC\Symfony\Cache\GatewayCachePurger;

/**
 * A GatewayCachePurger decorator that allows the actual purger to be switched on/off.
 */
class LegacySwitchableHttpCachePurger implements GatewayCachePurger
{
    use Switchable;

    /** @var \eZ\Publish\Core\MVC\Symfony\Cache\GatewayCachePurger */
    private $gatewayCachePurger;

    public function __construct(GatewayCachePurger $gatewayCachePurger)
    {
        $this->gatewayCachePurger = $gatewayCachePurger;
    }

    public function purge($locationIds)
    {
        if ($this->isSwitchedOff()) {
            return $locationIds;
        }

        $this->gatewayCachePurger->purge($locationIds);

        return $locationIds;
    }

    public function purgeAll()
    {
        if ($this->isSwitchedOff()) {
            return;
        }

        $this->gatewayCachePurger->purgeAll();
    }

    public function purgeForContent($contentId, $locationIds = array())
    {
        if ($this->isSwitchedOff()) {
            return;
        }

        $this->gatewayCachePurger->purgeForContent($contentId, $locationIds);
    }
}
