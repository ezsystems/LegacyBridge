<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\MVC\Legacy\SignalSlot;

use eZ\Publish\Core\SignalSlot\Signal;
use eZContentLanguage;

/**
 * A legacy slot handling UpdateLanguageNameSignal.
 */
class LegacyUpdateLanguageNameSlot extends AbstractLegacySlot
{
    /**
     * Receive the given $signal and react on it.
     *
     * @param \eZ\Publish\Core\SignalSlot\Signal $signal
     */
    public function receive(Signal $signal)
    {
        if (!$signal instanceof Signal\LanguageService\UpdateLanguageNameSignal) {
            return;
        }

        $this->runLegacyKernelCallback(
            static function () {
                eZContentLanguage::expireCache();
            }
        );
    }
}
