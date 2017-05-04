<?php

namespace eZ\Bundle\EzPublishLegacyBundle\Exception;

use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as BaseNotFoundHttpException;

class NotFoundHttpException extends BaseNotFoundHttpException
{
    /**
     * @var \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse
     */
    protected $originalResponse;

    /**
     * Constructor.
     *
     * @param string $message
     * @param \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse $originalResponse
     */
    public function __construct($message, LegacyResponse $originalResponse = null)
    {
        parent::__construct($message);

        $this->originalResponse = $originalResponse;
    }

    /**
     * Returns the response.
     *
     * @return \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse
     */
    public function getOriginalResponse()
    {
        return $this->originalResponse;
    }
}
