<?php
/**
 * File containing the LegacyKernelListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\EventListener;

use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreResetLegacyKernelEvent;
use eZINI;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\Core\MVC\Symfony\Event\ScopeChangeEvent;

/**
 * Resets eZINI when the Legacy Kernel is reset.
 * Resets legacy kernel handler when used in a command.
 */
class LegacyKernelListener implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            LegacyEvents::PRE_RESET_LEGACY_KERNEL => 'onKernelReset',
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    public function onKernelReset(PreResetLegacyKernelEvent $event)
    {
        $event->getLegacyKernel()->runCallback(
            static function () {
                eZINI::resetAllInstances();
            },
            true,
            false
        );
    }

    public function onConfigScopeChange(ScopeChangeEvent $event)
    {
        $this->resetKernelHandler();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $this->resetKernelHandler();

        $this->eventDispatcher->addListener(MVCEvents::CONFIG_SCOPE_CHANGE, [$this, 'onConfigScopeChange'], -1);
        $this->eventDispatcher->addListener(MVCEvents::CONFIG_SCOPE_RESTORE, [$this, 'onConfigScopeChange'], -1);
    }

    private function resetKernelHandler()
    {
        $legacyHandlerCLI = $this->container->get('ezpublish_legacy.kernel_handler.cli');
        $this->container->set('ezpublish_legacy.kernel.lazy', null);
        $this->container->set('ezpublish_legacy.kernel_handler', $legacyHandlerCLI);
        $this->container->set('ezpublish_legacy.kernel_handler.web', $legacyHandlerCLI);
    }
}
