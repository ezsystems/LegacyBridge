<?php
/**
 * File containing the Legacy\UpdateLocationSlot class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Core\MVC\Legacy\SignalSlot;

use eZ\Publish\Core\SignalSlot\Signal;
use eZContentCacheManager;
use eZContentObject;

/**
 * A legacy slot handling UpdateLocationSignal.
 */
class LegacyUpdateLocationSlot extends AbstractLegacySlot
{
    /**
     * Receive the given $signal and react on it.
     *
     * @param \eZ\Publish\Core\SignalSlot\Signal $signal
     */
    public function receive(Signal $signal)
    {
        if (!$signal instanceof Signal\LocationService\UpdateLocationSignal) {
            return;
        }

        $this->runLegacyKernelCallback(
            function () use ($signal) {
                eZContentCacheManager::clearContentCacheIfNeeded($signal->contentId);
                eZContentObject::clearCache(); // Clear all object memory cache to free memory
            }
        );
    }
}
