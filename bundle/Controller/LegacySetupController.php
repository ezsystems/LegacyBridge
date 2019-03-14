<?php
/**
 * File containing the LegacySetupController class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Controller;

use eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationConverter;
use eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper;
use eZ\Publish\Core\MVC\Legacy\Kernel\Loader;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use eZ\Publish\Core\MVC\Symfony\ConfigDumperInterface;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Configuration\LegacyConfigResolver;
use eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger;
use eZINI;
use eZCache;
use Symfony\Component\HttpKernel\KernelInterface;

class LegacySetupController
{
    /**
     * The legacy kernel instance (eZ Publish 4).
     *
     * @var \Closure
     */
    private $legacyKernelClosure;

    /**
     * The legacy config resolver.
     *
     * @var \eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Configuration\LegacyConfigResolver
     */
    private $legacyConfigResolver;

    /**
     * @var \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger
     */
    private $persistenceCachePurger;

    /**
     * @var \eZ\Publish\Core\MVC\Legacy\Kernel\Loader
     */
    protected $kernelFactory;

    /**
     * @var \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper
     */
    protected $configDumper;

    /**
     * @var \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationConverter
     */
    protected $configConverter;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected $kernel;

    public function __construct(
        \Closure $kernelClosure,
        LegacyConfigResolver $legacyConfigResolver,
        PersistenceCachePurger $persistenceCachePurger,
        Loader $kernelFactory,
        ConfigurationDumper $configDumper,
        ConfigurationConverter $configConverter,
        RequestStack $requestStack,
        KernelInterface $kernel
    ) {
        $this->legacyKernelClosure = $kernelClosure;
        $this->legacyConfigResolver = $legacyConfigResolver;
        $this->persistenceCachePurger = $persistenceCachePurger;
        $this->kernelFactory = $kernelFactory;
        $this->configDumper = $configDumper;
        $this->configConverter = $configConverter;
        $this->requestStack = $requestStack;
        $this->kernel = $kernel;
    }

    /**
     * @return \eZ\Publish\Core\MVC\Legacy\Kernel
     */
    protected function getLegacyKernel()
    {
        $legacyKernelClosure = $this->legacyKernelClosure;

        return $legacyKernelClosure();
    }

    public function init()
    {
        // Ensure that persistence cache purger is disabled as legacy cache will be cleared by legacy setup wizard while
        // everything is not ready yet to clear SPI cache (no connection to repository yet).
        $this->persistenceCachePurger->switchOff();

        // we disable injection of settings to Legacy Kernel during setup
        $this->kernelFactory->setBuildEventsEnabled(false);

        /** @var $request \Symfony\Component\HttpFoundation\ParameterBag */
        $request = $this->requestStack->getCurrentRequest()->request;

        // inject the extra ezpublish-community folders we want permissions checked for
        switch ($request->get('eZSetup_current_step')) {
            case 'Welcome':
            case 'SystemCheck':
                $this->getLegacyKernel()->runCallback(
                    static function () {
                        eZINI::injectSettings(
                            [
                                'setup.ini' => [
                                    // checked folders are relative to the ezpublish_legacy folder
                                    'directory_permissions' => [
                                        'CheckList' => '../app/logs;../app/cache;../app/config;' .
                                        eZINI::instance('setup.ini')->variable('directory_permissions', 'CheckList'),
                                    ],
                                ],
                            ]
                        );
                    }
                );
        }

        $response = new Response();
        $response->setContent(
            $this->getLegacyKernel()->run()->getContent()
        );

        // After the latest step, we can re-use both POST data and written INI settings
        // to generate a local ezpublish_<env>.yml

        // Clear INI cache since setup has written new files
        $this->getLegacyKernel()->runCallback(
            static function () {
                eZINI::injectSettings([]);
                eZCache::clearByTag('ini');
                eZINI::resetAllInstances();
            }
        );

        // Check that eZ Publish Legacy was actually installed, since one step can run several steps
        if ($this->legacyConfigResolver->getParameter('SiteAccessSettings.CheckValidity') == 'false') {
            // If using kickstart.ini, legacy wizard will artificially create entries in $_POST
            // and in this case Symfony Request is not aware of them.
            // We then add them manually to the ParameterBag.
            if (!$request->has('P_chosen_site_package-0')) {
                $request->add($_POST);
            }
            $chosenSitePackage = $request->get('P_chosen_site_package-0');

            // match mode (host, url or port)
            switch ($request->get('P_site_extra_data_access_type-' . $chosenSitePackage)) {
                case 'hostname':
                case 'port':
                    $adminSiteaccess = $chosenSitePackage . '_admin';
                    break;
                case 'url':
                    $adminSiteaccess = $request->get('P_site_extra_data_admin_access_type_value-' . $chosenSitePackage);
            }

            $this->configDumper->addEnvironment($this->kernel->getEnvironment());
            $this->configDumper->dump(
                $this->configConverter->fromLegacy($chosenSitePackage, $adminSiteaccess),
                ConfigDumperInterface::OPT_BACKUP_CONFIG
            );
        }

        return $response;
    }
}
