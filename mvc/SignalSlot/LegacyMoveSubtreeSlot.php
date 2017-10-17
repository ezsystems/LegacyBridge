<?php
/**
 * File containing the Legacy\MoveSubtreeSlot class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\SignalSlot;

use eZ\Publish\Core\SignalSlot\Signal;
use eZContentObject;
use eZContentObjectTreeNode;
use eZContentOperationCollection;

/**
 * A legacy slot handling MoveSubtreeSignal.
 */
class LegacyMoveSubtreeSlot extends AbstractLegacySlot
{
    /**
     * Receive the given $signal and react on it.
     *
     * @param \eZ\Publish\Core\SignalSlot\Signal $signal
     */
    public function receive(Signal $signal)
    {
        if (!$signal instanceof Signal\LocationService\MoveSubtreeSignal) {
            return;
        }

        $this->runLegacyKernelCallback(
            function () use ($signal) {
                $node = eZContentObjectTreeNode::fetch($signal->locationId);
                eZContentObjectTreeNode::clearViewCacheForSubtree($node);
                eZContentOperationCollection::registerSearchObject($node->attribute('contentobject_id'));
                eZContentObject::clearCache(); // Clear all object memory cache to free memory
            }
        );
    }
}
