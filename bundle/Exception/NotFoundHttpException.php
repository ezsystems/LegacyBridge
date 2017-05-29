<?php

namespace eZ\Bundle\EzPublishLegacyBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as BaseNotFoundHttpException;

class NotFoundHttpException extends BaseNotFoundHttpException
{
    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $originalResponse;

    /**
     * Constructor.
     *
     * @param string $message
     * @param \Symfony\Component\HttpFoundation\Response $originalResponse
     */
    public function __construct($message, Response $originalResponse = null)
    {
        parent::__construct($message);

        $this->originalResponse = $originalResponse;
    }

    /**
     * Returns the response.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOriginalResponse()
    {
        return $this->originalResponse;
    }
}
