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
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;

class WebsiteToolbarController extends Controller
{
    /** @var \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface */
    private $csrfProvider;

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

    public function __construct(
        EngineInterface $engine,
        ContentService $contentService,
        LocationService $locationService,
        AuthorizationCheckerInterface $authChecker,
        ContentPreviewHelper $previewHelper,
        CsrfProviderInterface $csrfProvider = null
    ) {
        $this->legacyTemplateEngine = $engine;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->authChecker = $authChecker;
        $this->csrfProvider = $csrfProvider;
        $this->previewHelper = $previewHelper;
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
    public function websiteToolbarAction($locationId, Request $request)
    {
        $response = new Response();

        if (isset($this->csrfProvider)) {
            $parameters['form_token'] = $this->csrfProvider->generateCsrfToken('legacy');
        }

        if ($this->previewHelper->isPreviewActive()) {
            $template = 'design:parts/website_toolbar_versionview.tpl';
            $previewedContent = $authValueObject = $this->previewHelper->getPreviewedContent();
            $previewedVersionInfo = $previewedContent->versionInfo;
            $parameters = array(
                'object' => $previewedContent,
                'version' => $previewedVersionInfo,
                'language' => $previewedVersionInfo->initialLanguageCode,
                'is_creator' => $previewedVersionInfo->creatorId === $this->getRepository()->getCurrentUser()->id,
            );
        } elseif ($locationId === null) {
            return $response;
        } else {
            $authValueObject = $this->loadContentByLocationId($locationId);
            $template = 'design:parts/website_toolbar.tpl';
            $parameters = array(
                'current_node_id' => $locationId,
                'redirect_uri' => $request->attributes->get('semanticPathinfo'),
            );
        }

        $authorizationAttribute = new AuthorizationAttribute(
            'websitetoolbar',
            'use',
            array('valueObject' => $authValueObject)
        );

        if (!$this->authChecker->isGranted($authorizationAttribute)) {
            return $response;
        }

        $response->setContent($this->legacyTemplateEngine->render($template, $parameters));

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
