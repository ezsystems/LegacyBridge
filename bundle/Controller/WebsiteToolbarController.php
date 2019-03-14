<?php
/**
 * File containing the WebsiteToolbarController class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Controller;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\Helper\ContentPreviewHelper;
use eZ\Publish\Core\MVC\Symfony\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;

class WebsiteToolbarController extends Controller
{
    /** @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface */
    private $csrfTokenManager;

    /** @var \Symfony\Component\Templating\EngineInterface */
    private $legacyTemplateEngine;

    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface */
    private $authChecker;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var ContentPreviewHelper */
    private $previewHelper;

    /** @var bool */
    private $viewCache;

    /** @var bool */
    private $ttlCache;

    /** @var int */
    private $defaultTtl;

    public function __construct(
        EngineInterface $engine,
        ContentService $contentService,
        LocationService $locationService,
        AuthorizationCheckerInterface $authChecker,
        ContentPreviewHelper $previewHelper,
        $viewCache,
        $ttlCache,
        $defaultTtl,
        CsrfTokenManagerInterface $csrfTokenManager = null
    ) {
        $this->legacyTemplateEngine = $engine;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->authChecker = $authChecker;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->previewHelper = $previewHelper;
        $this->viewCache = $viewCache;
        $this->ttlCache = $ttlCache;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Renders the legacy website toolbar template.
     *
     * If the logged in user doesn't have the required permission, an empty response is returned
     *
     * @param mixed $locationId
     * @param Request $request
     *
     * @return Response
     */
    public function websiteToolbarAction($locationId = null, $originalSemanticPathInfo = '', Request $request)
    {
        $response = $this->buildResponse();

        if (isset($this->csrfTokenManager)) {
            $parameters['form_token'] = $this->csrfTokenManager->getToken('legacy')->getValue();
        }

        if ($this->previewHelper->isPreviewActive()) {
            $template = 'design:parts/website_toolbar_versionview.tpl';
            $previewedContent = $authValueObject = $this->previewHelper->getPreviewedContent();
            $previewedVersionInfo = $previewedContent->versionInfo;
            $parameters = [
                'object' => $previewedContent,
                'version' => $previewedVersionInfo,
                'language' => $previewedVersionInfo->initialLanguageCode,
                'is_creator' => $previewedVersionInfo->creatorId === $this->getRepository()->getCurrentUser()->id,
            ];
        } elseif ($locationId === null) {
            return $response;
        } else {
            $authValueObject = $this->loadContentByLocationId($locationId);
            $template = 'design:parts/website_toolbar.tpl';
            $parameters = [
                'current_node_id' => $locationId,
                'redirect_uri' => $originalSemanticPathInfo ? $originalSemanticPathInfo : $request->attributes->get('semanticPathinfo'),
            ];
        }

        $authorizationAttribute = new AuthorizationAttribute(
            'websitetoolbar',
            'use',
            ['valueObject' => $authValueObject]
        );

        if (!$this->authChecker->isGranted($authorizationAttribute)) {
            return $response;
        }

        $response->setContent($this->legacyTemplateEngine->render($template, $parameters));

        return $response;
    }

    /**
     * Build the response so that depending on settings it's cacheable.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse()
    {
        $response = new Response();
        if ($this->viewCache === true) {
            $response->setPublic();

            if ($this->ttlCache === true) {
                $response->setSharedMaxAge(
                    $this->defaultTtl
                );
            }

            // Make the response vary against Cookie header ensures that an HTTP
            // reverse proxy caches the different possible variations of the
            // response as it can depend on user role for instance. X-User-Hash cannot
            // be used since the website toolbar can have Owner( Self ) Policy Limitation.
            $response->setVary('Cookie');
        }

        return $response;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected function loadContentByLocationId($locationId)
    {
        return $this->contentService->loadContent(
            $this->locationService->loadLocation($locationId)->contentId
        );
    }
}
