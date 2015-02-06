<?php
/**
 * This file is part of the eZ Publish LegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Core\MVC\Legacy\Session;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use ezpEvent;
use eZSession;
use eZDB;
use Closure;

/**
 * Session storage proxy for legacy.
 * Ensures that appropriate legacy session events are triggered whenever needed.
 */
class LegacySessionStorage implements SessionStorageInterface
{
    /**
     * @var SessionStorageInterface
     */
    private $innerSessionStorage;

    /**
     * @var callable
     */
    private $legacyKernelClosure;

    public function __construct( Closure $legacyKernelClosure, SessionStorageInterface $innerSessionStorage )
    {
        $this->innerSessionStorage = $innerSessionStorage;
        $this->legacyKernelClosure = $legacyKernelClosure;
    }

    public function start()
    {
        return $this->innerSessionStorage->start();
    }

    public function isStarted()
    {
        return $this->innerSessionStorage->isStarted();
    }

    public function getId()
    {
        return $this->innerSessionStorage->getId();
    }

    public function setId( $id )
    {
        $this->innerSessionStorage->setId( $id );
    }

    public function getName()
    {
        return $this->innerSessionStorage->getName();
    }

    public function setName( $name )
    {
        $this->innerSessionStorage->setName( $name );
    }

    /**
     * Ensures appropriate legacy events are sent when migrating the session.
     *
     * {@inheritdoc}
     */
    public function regenerate( $destroy = false, $lifetime = null )
    {
        $oldSessionId = $this->getId();
        $success = $this->innerSessionStorage->regenerate( $destroy, $lifetime );
        $newSessionId = $this->getId();

        if ( $success )
        {
            $kernelClosure = $this->legacyKernelClosure;
            $kernelClosure()->runCallback(
                function () use ( $oldSessionId, $newSessionId )
                {
                    ezpEvent::getInstance()->notify( 'session/regenerate', array( $oldSessionId, $newSessionId ) );
                    $db = eZDB::instance();
                    $escOldKey = $db->escapeString( $oldSessionId );
                    $escNewKey = $db->escapeString( $newSessionId );
                    $escUserID = $db->escapeString( eZSession::userID() );
                    eZSession::triggerCallback( 'regenerate_pre', array( $db, $escNewKey, $escOldKey, $escUserID ) );
                    eZSession::triggerCallback( 'regenerate_post', array( $db, $escNewKey, $escOldKey, $escUserID ) );
                },
                false
            );
        }

        return $success;
    }

    public function save()
    {
        $this->innerSessionStorage->save();
    }

    /**
     * Clear all session data in memory.
     */
    public function clear()
    {
        $this->innerSessionStorage->clear();
    }

    public function getBag( $name )
    {
        return $this->innerSessionStorage->getBag( $name );
    }

    public function registerBag( SessionBagInterface $bag )
    {
        $this->innerSessionStorage->registerBag( $bag );
    }

    public function getMetadataBag()
    {
        return $this->innerSessionStorage->getMetadataBag();
    }
}
