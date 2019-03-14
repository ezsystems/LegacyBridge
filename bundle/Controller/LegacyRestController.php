<?php
/**
 * File containing the LegacyRestController class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Controller;
use eZ\Publish\Core\MVC\Legacy\Kernel\Loader;
use ezpKernelRest;
use Symfony\Component\HttpFoundation\Response;

class LegacyRestController extends Controller
{
    /**
     * @var \ezpKernelHandler
     */
    protected $restKernel;

    public function __construct(\Closure $restKernelHandler, Loader $legacyKernelFactory, array $options = [])
    {
        $kernelClosure = $legacyKernelFactory->buildLegacyKernel($restKernelHandler);
        $this->restKernel = $kernelClosure();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restAction()
    {
        $this->restKernel->run();

        $result = ezpKernelRest::getResponse();
        if ($result === null) {
            throw new \Exception('Rest Kernel run failed');
        }

        return new Response(
            $result->getContent(),
            $result->hasAttribute('statusCode') ? $result->getAttribute('statusCode') : 200,
            $result->hasAttribute('headers') ? $result->getAttribute('headers') : []
        );
    }
}
