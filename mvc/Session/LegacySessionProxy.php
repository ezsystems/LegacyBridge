<?php
/**
 * This file is part of the eZ Publish LegacyBridge package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace eZ\Publish\Core\MVC\Legacy\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;
use SessionHandlerInterface;
use Closure;
use ezpEvent;
use eZDB;
use eZSession;

/**
 * Session handler proxy for legacy.
 * Ensures that appropriate legacy session events are triggered whenever needed.
 */
class LegacySessionProxy extends AbstractProxy implements SessionHandlerInterface
{
    /**
     * @var callable
     */
    private $legacyKernelClosure;

    /**
     * @var SessionHandlerInterface|null
     */
    private $sessionHandler;

    public function __construct( Closure $legacyKernelClosure, SessionHandlerInterface $sessionHandler = null )
    {
        $this->legacyKernelClosure = $legacyKernelClosure;
        $this->sessionHandler = $sessionHandler;
        $this->wrapper = true;
        $this->saveHandlerName = ini_get( 'session.save_handler' );
    }

    /**
     * @return \ezpKernelHandler
     */
    private function getLegacyKernel()
    {
        $closure = $this->legacyKernelClosure;
        return $closure();
    }

    public function open( $savePath, $sessionName )
    {
        $return = true;
        if ( $this->sessionHandler )
        {
            $return = (bool)$this->sessionHandler->open( $savePath, $sessionName );
        }

        if ( $return === true )
        {
            $this->active = true;
        }

        return $return;
    }

    public function close()
    {
        $this->active = false;

        if ( $this->sessionHandler )
        {
            return (bool)$this->sessionHandler->close();
        }

        return true;
    }

    public function read($sessionId)
    {
        if ( $this->sessionHandler )
        {
            return (string)$this->sessionHandler->read( $sessionId );
        }

        return '';
    }

    public function write( $sessionId, $data )
    {
        if ( $this->sessionHandler )
        {
            return (bool)$this->sessionHandler->write( $sessionId, $data );
        }

        return false;
    }

    public function destroy( $sessionId )
    {
        $this->getLegacyKernel()->runCallback(
            function () use ( $sessionId )
            {
                ezpEvent::getInstance()->notify( 'session/destroy', array( $sessionId ) );
            },
            false
        );

        if ( $this->sessionHandler )
        {
            return $this->sessionHandler->destroy( $sessionId );
        }

        return false;
    }

    public function gc( $maxlifetime )
    {
        $sessionHandler = $this->sessionHandler;
        return $this->getLegacyKernel()->runCallback(
            function () use ( $maxlifetime, $sessionHandler )
            {
                ezpEvent::getInstance()->notify( 'session/gc', array( $maxlifetime ) );
                $db = eZDB::instance();
                eZSession::triggerCallback( 'gc_pre', array( $db, $maxlifetime ) );

                $success = false;
                if ( $sessionHandler )
                {
                    $success = $sessionHandler->gc( $maxlifetime );
                }

                eZSession::triggerCallback( 'gc_post', array( $db, $maxlifetime ) );
                return $success;
            },
            false
        );
    }
}
