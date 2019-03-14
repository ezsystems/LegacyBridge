<?php
/**
 * File containing the LegacyCreateUserSlot class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\SignalSlot;

use eZ\Publish\Core\SignalSlot\Signal;
use eZContentCacheManager;
use eZContentObject;
use eZContentOperationCollection;

/**
 * A legacy slot handling CreateUserSignal.
 */
class LegacyCreateUserSlot extends AbstractLegacySlot
{
    /**
     * Receive the given $signal and react on it.
     *
     * @param \eZ\Publish\Core\SignalSlot\Signal $signal
     */
    public function receive(Signal $signal)
    {
        if (!$signal instanceof Signal\UserService\CreateUserSignal) {
            return;
        }

        $this->runLegacyKernelCallback(
            static function () use ($signal) {
                eZContentCacheManager::clearContentCacheIfNeeded($signal->userId);
                eZContentOperationCollection::registerSearchObject($signal->userId);
                eZContentObject::clearCache(); // Clear all object memory cache to free memory
            }
        );
    }
}
