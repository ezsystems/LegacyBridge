<?php
/**
 * This file is part of the eZ LegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Security;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\RememberMeListener as BaseRememberMeListener;

class RememberMeListener extends BaseRememberMeListener
{
    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @param ConfigResolverInterface $configResolver
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function handle(GetResponseEvent $event)
    {
        // In legacy_mode, "remember me" must be delegated to legacy kernel.
        if ($this->configResolver->getParameter('legacy_mode')) {
            return;
        }

        parent::handle($event);
    }
}
