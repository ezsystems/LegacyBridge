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

    public function __construct(Closure $legacyKernelClosure, SessionHandlerInterface $sessionHandler)
    {
        $this->legacyKernelClosure = $legacyKernelClosure;
        $this->sessionHandler = $sessionHandler;
        $this->wrapper = true;
        $this->saveHandlerName = ini_get('session.save_handler');
    }

    /**
     * @return \ezpKernelHandler
     */
    private function getLegacyKernel()
    {
        $closure = $this->legacyKernelClosure;

        return $closure();
    }

    public function open($savePath, $sessionName)
    {
        $return = (bool)$this->sessionHandler->open($savePath, $sessionName);

        if ($return === true) {
            $this->active = true;
        }

        return $return;
    }

    public function close()
    {
        $this->active = false;

        return (bool)$this->sessionHandler->close();
    }

    public function read($sessionId)
    {
        return (string)$this->sessionHandler->read($sessionId);
    }

    public function write($sessionId, $data)
    {
        return (bool)$this->sessionHandler->write($sessionId, $data);
    }

    public function destroy($sessionId)
    {
        $this->getLegacyKernel()->runCallback(
            static function () use ($sessionId) {
                ezpEvent::getInstance()->notify('session/destroy', [$sessionId]);
            },
            false
        );

        return $this->sessionHandler->destroy($sessionId);
    }

    public function gc($maxlifetime)
    {
        $sessionHandler = $this->sessionHandler;

        return $this->getLegacyKernel()->runCallback(
            static function () use ($maxlifetime, $sessionHandler) {
                ezpEvent::getInstance()->notify('session/gc', [$maxlifetime]);
                $db = eZDB::instance();
                eZSession::triggerCallback('gc_pre', [$db, $maxlifetime]);

                $success = $sessionHandler->gc($maxlifetime);

                eZSession::triggerCallback('gc_post', [$db, $maxlifetime]);

                return $success;
            },
            false
        );
    }
}
