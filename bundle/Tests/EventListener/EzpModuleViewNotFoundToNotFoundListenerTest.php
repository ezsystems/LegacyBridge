<?php
/**
 * File containing the EzpModuleViewNotFoundToNotFoundListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use ezpModuleViewNotFound;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use eZ\Bundle\EzPublishLegacyBundle\EventListener\EzpModuleViewNotFoundToNotFoundListener;

class EzpModuleViewNotFoundToNotFoundListenerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            array(
                KernelEvents::EXCEPTION => ['onException', 512],
            ),
            EzpModuleViewNotFoundToNotFoundListener::getSubscribedEvents()
        );
    }

    public function testOnException()
    {
        $exception = new ezpModuleViewNotFound('module-name', 'view-name');
        $request = $this->createMock(Request::class);
        $event = new GetResponseForExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
        $listener = new EzpModuleViewNotFoundToNotFoundListener();
        $listener->onException($event);
        $this->assertInstanceOf(NotFoundHttpException::class, $event->getException());
        $this->assertSame($exception, $event->getException()->getPrevious());
    }
}
