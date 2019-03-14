<?php
/**
 * File containing the SiteAccess class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\LegacyMapper;

use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelWebHandlerEvent;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher\CompoundInterface;
use eZSiteAccess;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps the SiteAccess object to the legacy parameters.
 */
class SiteAccess implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public static function getSubscribedEvents()
    {
        return [
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL_WEB => ['onBuildKernelWebHandler', 128],
        ];
    }

    /**
     * Maps matched siteaccess to the legacy parameters.
     *
     * @param \eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelWebHandlerEvent $event
     */
    public function onBuildKernelWebHandler(PreBuildKernelWebHandlerEvent $event)
    {
        $siteAccess = $this->container->get('ezpublish.siteaccess');
        $request = $event->getRequest();
        $uriPart = [];

        // Convert matching type
        switch ($siteAccess->matchingType) {
            case 'default':
                $legacyAccessType = eZSiteAccess::TYPE_DEFAULT;
                break;

            case 'env':
                $legacyAccessType = eZSiteAccess::TYPE_SERVER_VAR;
                break;

            case 'uri:map':
            case 'uri:element':
            case 'uri:text':
            case 'uri:regexp':
                $legacyAccessType = eZSiteAccess::TYPE_URI;
                break;

            case 'host:map':
            case 'host:element':
            case 'host:text':
            case 'host:regexp':
                $legacyAccessType = eZSiteAccess::TYPE_HTTP_HOST;
                break;

            case 'port':
                $legacyAccessType = eZSiteAccess::TYPE_PORT;
                break;

            default:
                $legacyAccessType = eZSiteAccess::TYPE_CUSTOM;
        }

        // uri_part
        $pathinfo = str_replace($request->attributes->get('viewParametersString'), '', rawurldecode($request->getPathInfo()));
        $fragmentPathInfo = implode('/', $this->getFragmentPathItems());
        if ($fragmentPathInfo !== '_fragment') {
            $semanticPathinfo = $fragmentPathInfo;
        } else {
            $semanticPathinfo = $request->attributes->get('semanticPathinfo', $pathinfo);
        }

        $pos = mb_strrpos($pathinfo, $semanticPathinfo);
        if ($legacyAccessType !== eZSiteAccess::TYPE_DEFAULT && $pathinfo != $semanticPathinfo && $pos !== false) {
            if ($pos === 0) {
                $pos = mb_strlen($pathinfo) + 1;
            }
            $uriPart = mb_substr($pathinfo, 1, $pos - 1);
            $uriPart = explode('/', $uriPart);
        }

        // Handle host_uri match
        if ($siteAccess->matcher instanceof CompoundInterface) {
            $subMatchers = $siteAccess->matcher->getSubMatchers();
            if (!$subMatchers) {
                throw new \RuntimeException('Compound matcher used but not submatchers found.');
            }

            if (\count($subMatchers) == 2 && isset($subMatchers['Map\Host']) && isset($subMatchers['Map\URI'])) {
                $legacyAccessType = eZSiteAccess::TYPE_HTTP_HOST_URI;
                $uriPart = [$subMatchers['Map\URI']->getMapKey()];
            }
        }

        $event->getParameters()->set(
            'siteaccess',
            [
                'name' => $siteAccess->name,
                'type' => $legacyAccessType,
                'uri_part' => $uriPart,
            ]
        );
    }

    /**
     * Returns an array with all the components of the fragment_path option.
     * @return array
     */
    protected function getFragmentPathItems()
    {
        if (isset($this->options['fragment_path'])) {
            return explode('/', trim($this->options['fragment_path'], '/'));
        }

        return ['_fragment'];
    }
}
