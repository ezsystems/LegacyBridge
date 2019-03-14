<?php
/**
 * File containing the Session class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\LegacyMapper;

use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Symfony\RequestStackAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * Maps the session parameters to the legacy parameters.
 */
class Session implements EventSubscriberInterface
{
    use RequestStackAware;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface
     */
    private $sessionStorage;

    /**
     * @var string
     */
    private $sessionStorageKey;

    public function __construct(SessionStorageInterface $sessionStorage, $sessionStorageKey, SessionInterface $session = null)
    {
        $this->sessionStorage = $sessionStorage;
        $this->sessionStorageKey = $sessionStorageKey;
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL => ['onBuildKernelHandler', 128],
        ];
    }

    /**
     * Adds the session settings to the parameters that will be injected
     * into the legacy kernel.
     *
     * @param \eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent $event
     */
    public function onBuildKernelHandler(PreBuildKernelEvent $event)
    {
        $sessionInfos = [
            'configured' => false,
            'started' => false,
            'name' => false,
            'namespace' => false,
            'has_previous' => false,
            'storage' => false,
        ];
        if (isset($this->session)) {
            $request = $this->getCurrentRequest();
            $sessionInfos['configured'] = true;
            $sessionInfos['name'] = $this->session->getName();
            $sessionInfos['started'] = $this->session->isStarted();
            $sessionInfos['namespace'] = $this->sessionStorageKey;
            $sessionInfos['has_previous'] = isset($request) ? $request->hasPreviousSession() : false;
            $sessionInfos['storage'] = $this->sessionStorage;
        }

        $legacyKernelParameters = $event->getParameters();
        $legacyKernelParameters->set('session', $sessionInfos);

        // Deactivate session cookie settings in legacy kernel.
        // This will force using settings defined in Symfony.
        $sessionSettings = [
            'site.ini/Session/CookieTimeout' => false,
            'site.ini/Session/CookiePath' => false,
            'site.ini/Session/CookieDomain' => false,
            'site.ini/Session/CookieSecure' => false,
            'site.ini/Session/CookieHttponly' => false,
        ];
        $legacyKernelParameters->set(
            'injected-settings',
            $sessionSettings + (array)$legacyKernelParameters->get('injected-settings')
        );
    }
}
