<?php
/**
 * File containing the EzPublishLegacy class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle;

use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\RememberMeListenerPass;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\LegacyBundlesPass;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\LegacySessionPass;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\RelatedSiteAccessesCleanupPass;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\RoutingPass;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Security\SSOFactory;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\LegacyPass;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Compiler\TwigPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class EzPublishLegacyBundle extends Bundle
{
    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function boot()
    {
        if (!$this->container->getParameter('ezpublish_legacy.enabled')) {
            return;
        }

        // Deactivate eZComponents loading from legacy autoload.php as they are already loaded
        if (!defined('EZCBASE_ENABLED')) {
            define('EZCBASE_ENABLED', false);
        }

        require_once $this->container->getParameter('ezpublish_legacy.root_dir') . '/autoload.php';
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RelatedSiteAccessesCleanupPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new LegacyPass());
        $container->addCompilerPass(new TwigPass());
        $container->addCompilerPass(new LegacyBundlesPass($this->kernel));
        $container->addCompilerPass(new RoutingPass());
        $container->addCompilerPass(new LegacySessionPass());
        $container->addCompilerPass(new RememberMeListenerPass());

        /** @var \Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension $securityExtension */
        $securityExtension = $container->getExtension('security');
        $securityExtension->addSecurityListenerFactory(new SSOFactory());
    }
}
