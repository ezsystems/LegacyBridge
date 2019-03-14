<?php
/**
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Rest;

use ezcMvcResultCache;
use ezcMvcResultContent;
use ezcMvcResultStatusObject;
use ezpKernelRest;
use ezpKernelResult;
use ezpRestHttpResponseWriter;

class ResponseWriter extends ezpRestHttpResponseWriter
{
    public function handleResponse()
    {
        // process all headers
        $this->processStandardHeaders();
        if ($this->response->cache instanceof ezcMvcResultCache) {
            $this->processCacheHeaders();
        }
        if ($this->response->content instanceof ezcMvcResultContent) {
            $this->processContentHeaders();
        }

        // process the status headers through objects
        if (!$this->response->status instanceof ezcMvcResultStatusObject) {
            $responseCode = 200;
        } else {
            $responseCode = $this->response->status->code;
        }

        // automatically add content-length header
        $this->headers['Content-Length'] = \strlen($this->response->body);

        ezpKernelRest::setResponse(
            new ezpKernelResult($this->response->body, ['headers' => $this->headers, 'statusCode' => $responseCode])
        );
    }
}
