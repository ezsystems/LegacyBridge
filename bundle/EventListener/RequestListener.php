<?php
/**
 * File containing the RequestListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\EventListener;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Legacy\Security\LegacyToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use eZ\Publish\Core\MVC\Symfony\Security\User;

class RequestListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    private $repository;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(ConfigResolverInterface $configResolver, Repository $repository, TokenStorageInterface $tokenStorage)
    {
        $this->configResolver = $configResolver;
        $this->repository = $repository;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }

    /**
     * If user is logged-in in legacy_mode (e.g. legacy admin interface),
     * will inject currently logged-in user in the repository.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver */
        $request = $event->getRequest();
        $session = $request->getSession();
        if (
            $event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST
            || !$this->configResolver->getParameter('legacy_mode')
            || !($session->isStarted() && $session->has('eZUserLoggedInID'))
        ) {
            return;
        }

        try {
            $apiUser = $this->repository->getUserService()->loadUser($session->get('eZUserLoggedInID'));
            $this->repository->setCurrentUser($apiUser);

            $token = $this->tokenStorage->getToken();
            if ($token instanceof TokenInterface) {
                $token->setUser(new User($apiUser));
                // Don't embed if we already have a LegacyToken, to avoid nested session storage.
                if (!$token instanceof LegacyToken) {
                    $this->tokenStorage->setToken(new LegacyToken($token));
                }
            }
        } catch (NotFoundException $e) {
            // Invalid user ID, the user may have been removed => invalidate the token and the session.
            $this->tokenStorage->setToken(null);
            $session->invalidate();
        }
    }
}
