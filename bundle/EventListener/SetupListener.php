<?php
/**
 * This file is part of the legacy-bridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class SetupListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $defaultSiteAccess;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router, $defaultSiteAccess)
    {
        $this->defaultSiteAccess = $defaultSiteAccess;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestSetup', 190],
            ],
        ];
    }

    /**
     * Checks if it's needed to redirect to setup wizard.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequestSetup(GetResponseEvent $event)
    {
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
            if ($this->defaultSiteAccess !== 'setup') {
                return;
            }

            $request = $event->getRequest();
            $requestContext = $this->router->getContext();
            $requestContext->fromRequest($request);
            $this->router->setContext($requestContext);
            $setupURI = $this->router->generate('ezpublishSetup');

            if (($requestContext->getBaseUrl() . $request->getPathInfo()) === $setupURI) {
                return;
            }

            $event->setResponse(new RedirectResponse($setupURI));
        }
    }
}
