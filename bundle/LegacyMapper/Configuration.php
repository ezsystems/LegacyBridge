<?php
/**
 * File containing the Configuration class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\LegacyMapper;

use eZ\Publish\Core\FieldType\Image\AliasCleanerInterface;
use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface;
use eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger;
use eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use eZ\Publish\Core\Persistence\Database\DatabaseHandler;
use ezpEvent;
use ezxFormToken;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use RuntimeException;
use Exception;

/**
 * Maps configuration parameters to the legacy parameters.
 */
class Configuration implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var \EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface
     */
    private $purgeClient;

    /**
     * @var \eZ\Bundle\EzPublishLegacyBundle\Cache\PersistenceCachePurger
     */
    private $persistenceCachePurger;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator
     */
    private $urlAliasGenerator;

    /**
     * @var \eZ\Publish\Core\Persistence\Database\DatabaseHandler
     */
    private $legacyDbHandler;

    /**
     * @var array
     */
    private $options;

    /**
     * Disables the feature when set using setEnabled().
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * @var AliasCleanerInterface
     */
    private $aliasCleaner;

    public function __construct(
        ConfigResolverInterface $configResolver,
        PurgeClientInterface $purgeClient,
        PersistenceCachePurger $persistenceCachePurger,
        UrlAliasGenerator $urlAliasGenerator,
        DatabaseHandler $legacyDbHandler,
        AliasCleanerInterface $aliasCleaner,
        array $options = []
    ) {
        $this->configResolver = $configResolver;
        $this->purgeClient = $purgeClient;
        $this->persistenceCachePurger = $persistenceCachePurger;
        $this->urlAliasGenerator = $urlAliasGenerator;
        $this->legacyDbHandler = $legacyDbHandler;
        $this->aliasCleaner = $aliasCleaner;
        $this->options = $options;
    }

    /**
     * Toggles the feature.
     *
     * @param bool $isEnabled
     */
    public function setEnabled($isEnabled)
    {
        $this->enabled = (bool)$isEnabled;
    }

    public static function getSubscribedEvents()
    {
        return [
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL => ['onBuildKernel', 128],
        ];
    }

    /**
     * Adds settings to the parameters that will be injected into the legacy kernel.
     *
     * @param \eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent $event
     */
    public function onBuildKernel(PreBuildKernelEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $databaseSettings = $this->legacyDbHandler->getConnection()->getParams();
        $settings = [];
        foreach (
            [
                'host' => 'Server',
                'port' => 'Port',
                'user' => 'User',
                'password' => 'Password',
                'dbname' => 'Database',
                'unix_socket' => 'Socket',
                'driver' => 'DatabaseImplementation',
            ] as $key => $iniKey
        ) {
            if (isset($databaseSettings[$key])) {
                $iniValue = $databaseSettings[$key];

                switch ($key) {
                    case 'driver':
                        $driverMap = [
                            'pdo_mysql' => 'ezmysqli',
                            'pdo_pgsql' => 'ezpostgresql',
                            'oci8' => 'ezoracle',
                        ];
                        if (!isset($driverMap[$iniValue])) {
                            throw new RuntimeException(
                                "Could not map database driver to Legacy Stack database implementation.\n" .
                                "Expected one of '" . implode("', '", array_keys($driverMap)) . "', got '" .
                                $iniValue . "'."
                            );
                        }
                        $iniValue = $driverMap[$iniValue];
                        break;
                }

                $settings["site.ini/DatabaseSettings/$iniKey"] = $iniValue;
            }
            // Some settings need specific values when not present.
            else {
                switch ($key) {
                    case 'unix_socket':
                        $settings["site.ini/DatabaseSettings/$iniKey"] = 'disabled';
                        break;
                }
            }
        }

        // Image settings
        $settings += $this->getImageSettings();
        // File settings
        $settings += [
            'site.ini/FileSettings/VarDir' => $this->configResolver->getParameter('var_dir'),
            'site.ini/FileSettings/StorageDir' => $this->configResolver->getParameter('storage_dir'),
        ];
        // Multisite settings (PathPrefix and co)
        $settings += $this->getMultiSiteSettings();

        // User settings
        $settings['site.ini/UserSettings/AnonymousUserID'] = $this->configResolver->getParameter('anonymous_user_id');

        // Cache settings
        // Enforce ViewCaching to be enabled in order to persistence/http cache to be purged correctly.
        $settings['site.ini/ContentSettings/ViewCaching'] = 'enabled';

        // Cluster Settings
        $settings += $this->getClusterSettings();

        $event->getParameters()->set(
            'injected-settings',
            $settings + (array)$event->getParameters()->get('injected-settings')
        );

        if (class_exists('ezxFormToken')) {
            // Inject csrf protection settings to make sure legacy & symfony stack work together
            if (
                $this->container->hasParameter('form.type_extension.csrf.enabled') &&
                $this->container->getParameter('form.type_extension.csrf.enabled')
            ) {
                ezxFormToken::setSecret($this->container->getParameter('kernel.secret'));
                ezxFormToken::setFormField($this->container->getParameter('form.type_extension.csrf.field_name'));
            }
            // csrf protection is disabled, disable it in legacy extension as well.
            else {
                ezxFormToken::setIsEnabled(false);
            }
        }

        // Register http cache content/cache event listener
        $ezpEvent = ezpEvent::getInstance();
        $ezpEvent->attach('content/cache', [$this->purgeClient, 'purge']);
        $ezpEvent->attach('content/cache/all', [$this->purgeClient, 'purgeAll']);

        // Register persistence cache event listeners
        $ezpEvent->attach('content/cache', [$this->persistenceCachePurger, 'content']);
        $ezpEvent->attach('content/cache/all', [$this->persistenceCachePurger, 'all']);
        $ezpEvent->attach('content/cache/version', [$this->persistenceCachePurger, 'contentVersion']);
        $ezpEvent->attach('content/class/cache/all', [$this->persistenceCachePurger, 'contentType']);
        $ezpEvent->attach('content/class/cache', [$this->persistenceCachePurger, 'contentType']);
        $ezpEvent->attach('content/class/group/cache', [$this->persistenceCachePurger, 'contentTypeGroup']);
        $ezpEvent->attach('content/section/cache', [$this->persistenceCachePurger, 'section']);
        $ezpEvent->attach('user/cache/all', [$this->persistenceCachePurger, 'user']);
        $ezpEvent->attach('content/translations/cache', [$this->persistenceCachePurger, 'languages']);
        $ezpEvent->attach('content/state/assign', [$this->persistenceCachePurger, 'stateAssign']);

        // Register image alias removal listeners
        $ezpEvent->attach('image/removeAliases', [$this->aliasCleaner, 'removeAliases']);
        $ezpEvent->attach('image/trashAliases', [$this->aliasCleaner, 'removeAliases']);
        $ezpEvent->attach('image/purgeAliases', [$this->aliasCleaner, 'removeAliases']);
    }

    private function getImageSettings()
    {
        $imageSettings = [
            // Basic settings
            'image.ini/FileSettings/TemporaryDir' => $this->configResolver->getParameter('image.temporary_dir'),
            'image.ini/FileSettings/PublishedImages' => $this->configResolver->getParameter('image.published_images_dir'),
            'image.ini/FileSettings/VersionedImages' => $this->configResolver->getParameter('image.versioned_images_dir'),
            'image.ini/AliasSettings/AliasList' => [],
            // ImageMagick configuration
            'image.ini/ImageMagick/IsEnabled' => $this->options['imagemagick_enabled'] ? 'true' : 'false',
            'image.ini/ImageMagick/ExecutablePath' => $this->options['imagemagick_executable_path'],
            'image.ini/ImageMagick/Executable' => $this->options['imagemagick_executable'],
            'image.ini/ImageMagick/PreParameters' => $this->configResolver->getParameter('imagemagick.pre_parameters'),
            'image.ini/ImageMagick/PostParameters' => $this->configResolver->getParameter('imagemagick.post_parameters'),
            'image.ini/ImageMagick/Filters' => [],
        ];

        // Aliases configuration
        $imageVariations = $this->configResolver->getParameter('image_variations');
        foreach ($imageVariations as $aliasName => $aliasSettings) {
            $imageSettings['image.ini/AliasSettings/AliasList'][] = $aliasName;
            if (isset($aliasSettings['reference'])) {
                $imageSettings["image.ini/$aliasName/Reference"] = $aliasSettings['reference'];
            }

            foreach ($aliasSettings['filters'] as $filterName => $filter) {
                if (!isset($this->options['imagemagick_filters'][$filterName])) {
                    continue;
                }
                $imageSettings["image.ini/$aliasName/Filters"][] = $filterName . '=' . implode(';', $filter);
            }
        }

        foreach ($this->options['imagemagick_filters'] as $filterName => $filter) {
            $imageSettings['image.ini/ImageMagick/Filters'][] = "$filterName=" . strtr($filter, ['{' => '%', '}' => '']);
        }

        return $imageSettings;
    }

    private function getMultiSiteSettings()
    {
        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id');
        $indexPage = $this->configResolver->getParameter('index_page');
        $defaultPage = $this->configResolver->getParameter('default_page');
        if ($rootLocationId === null) {
            // return SiteSettings if there is no MultiSite (rootLocation is not defined)
            $result = [];
            if ($indexPage !== null) {
                $result['site.ini/SiteSettings/IndexPage'] = $indexPage;
            }
            if ($defaultPage !== null) {
                $result['site.ini/SiteSettings/DefaultPage'] = $defaultPage;
            }

            return $result;
        }

        $pathPrefix = $this->loadPathPrefix($rootLocationId);
        $pathPrefixExcludeItems = array_map(
            static function ($value) {
                return trim($value, '/');
            },
            $this->configResolver->getParameter('content.tree_root.excluded_uri_prefixes')
        );

        return [
            'site.ini/SiteAccessSettings/PathPrefix' => $pathPrefix,
            'site.ini/SiteAccessSettings/PathPrefixExclude' => $pathPrefixExcludeItems,
            'logfile.ini/AccessLogFileSettings/PathPrefix' => $pathPrefix,
            'site.ini/SiteSettings/IndexPage' => $indexPage !== null ? $indexPage : "/content/view/full/$rootLocationId/",
            'site.ini/SiteSettings/DefaultPage' => $defaultPage !== null ? $defaultPage : "/content/view/full/$rootLocationId/",
        ];
    }

    private function getClusterSettings()
    {
        $clusterSettings = [];
        if ($this->container->hasParameter('dfs_nfs_path')) {
            $clusterSettings += [
                'file.ini/ClusteringSettings/FileHandler' => 'eZDFSFileHandler',
                'file.ini/eZDFSClusteringSettings/MountPointPath' => $this->container->getParameter('dfs_nfs_path'),
                'file.ini/eZDFSClusteringSettings/DBHost' => $this->container->getParameter('dfs_database_host'),
                'file.ini/eZDFSClusteringSettings/DBPort' => $this->container->getParameter('dfs_database_port'),
                'file.ini/eZDFSClusteringSettings/DBName' => $this->container->getParameter('dfs_database_name'),
                'file.ini/eZDFSClusteringSettings/DBUser' => $this->container->getParameter('dfs_database_user'),
                'file.ini/eZDFSClusteringSettings/DBPassword' => $this->container->getParameter('dfs_database_password'),
            ];
        }

        return $clusterSettings;
    }

    private function loadPathPrefix($rootLocationId)
    {
        // If root location is 2 we know path is empty, so we can skip loading location + urlAlias data
        if ($rootLocationId === 2) {
            return '';
        }

        try {
            return trim($this->urlAliasGenerator->getPathPrefixByRootLocationId($rootLocationId), '/');
        } catch (Exception $e) {
            // Ignore any errors
            // Most probable cause for error is database not being ready yet,
            // i.e. initial install of the project which includes eZ Publish Legacy
        }

        return '';
    }
}
