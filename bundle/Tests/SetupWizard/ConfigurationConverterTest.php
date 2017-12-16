<?php
/**
 * File containing the ConfigurationConverterTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\SetupWizard;

use eZ\Publish\Core\MVC\Legacy\Tests\LegacyBasedTestCase;
use eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationConverter;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\MVC\Legacy\Kernel;
use eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Configuration\LegacyConfigResolver;
use Exception;
use ezpKernelResult;

class ConfigurationConverterTest extends LegacyBasedTestCase
{
    protected function getConfigurationConverterMock(array $constructorParams)
    {
        return $this->getMockBuilder(ConfigurationConverter::class)
            ->setConstructorArgs($constructorParams)
            ->setMethods([
                'getParameter',
                'getGroup',
            ])
            ->getMock();
    }

    /**
     * @param string $package
     * @param string $adminSiteaccess
     * @param array $mockParameters
     * @param mixed $expectedResult
     * @param string $exception exception type, if expected
     *
     * @throws \Exception
     *
     * @dataProvider providerForTestFromLegacy
     */
    public function testFromLegacy($package, $adminSiteaccess, $mockParameters, $expectedResult, $exception = null)
    {
        $configurationConverter = $this->getConfigurationConverterMock(
            array(
                $this->getLegacyConfigResolverMock(),
                $this->getLegacyKernelMock(),
                array($package),
            )
        );
        foreach ($mockParameters as $method => $callbackMap) {
            $configurationConverter->expects($this->any())
                ->method($method)
                ->will(
                    $this->returnCallback(
                        $this->convertMapToCallback($callbackMap)
                    )
                );
        }

        try {
            $result = $configurationConverter->fromLegacy($package, $adminSiteaccess);
        } catch (Exception $e) {
            if ($exception !== null && $e instanceof $exception) {
                return;
            } else {
                throw $e;
            }
        }

        ksort($expectedResult);
        ksort($expectedResult['ezpublish']);
        self::assertEquals(
            $expectedResult,
            $result
        );
    }

    /**
     * Converts a map of arguments + return value to a callback in order to allow exceptions.
     *
     * @param array[] $callbackMap array of callback parameter arrays [0..n-1 => arguments, n => return value]
     *
     * @return callable
     */
    protected function convertMapToCallback($callbackMap)
    {
        return function () use ($callbackMap) {
            foreach ($callbackMap as $map) {
                $mapArguments = array_slice($map, 0, -1);
                // pad the call arguments array with nulls to match the map
                $callArguments = array_pad(func_get_args(), count($mapArguments), null);

                if (count(array_diff($callArguments, $mapArguments)) == 0) {
                    $return = $map[count($map) - 1];
                    if (is_callable($return)) {
                        return $return();
                    } else {
                        return $return;
                    }
                }
            }
            throw new \Exception('No callback match found for ' . var_export(func_get_args(), true));
        };
    }

    public function providerForTestFromLegacy()
    {
        define('IDX_PACKAGE', 0);
        define('IDX_ADMIN_SITEACCESS', 1);
        define('IDX_MOCK_PARAMETERS', 2);
        define('IDX_EXPECTED_RESULT', 3);
        define('IDX_EXCEPTION', 4);

        $commonResult = array(
            'doctrine' => array(
                'dbal' => array(
                    'connections' => array(
                        'eng_repository_connection' => array(
                            'driver' => 'pdo_mysql',
                            'user' => 'root',
                            'password' => null,
                            'host' => 'localhost',
                            'dbname' => 'ezdemo',
                            'charset' => 'UTF8',
                        ),
                    ),
                ),
            ),
            'ez_publish_legacy' => array(
                'enabled' => true,
                'system' => array(
                    'ezdemo_site_admin' => array(
                        'legacy_mode' => true,
                    ),
                ),
            ),
            'ezpublish' => array(
                'repositories' => array(
                    'eng_repository' => array('engine' => 'legacy', 'connection' => 'eng_repository_connection'),
                ),
                'siteaccess' => array(
                    'default_siteaccess' => 'eng',
                    'list' => array(
                        0 => 'eng',
                        1 => 'ezdemo_site',
                        2 => 'ezdemo_site_admin',
                    ),
                    'groups' => array(
                        'ezdemo_group' => array(
                            0 => 'eng',
                            1 => 'ezdemo_site',
                            2 => 'ezdemo_site_admin',
                        ),
                    ),
                    'match' => array('URIElement' => 1),
                ),
                'system' => array(
                    'ezdemo_group' => array(
                        'repository' => 'eng_repository',
                        'var_dir' => 'var/ezdemo_site',
                        'languages' => array('eng-GB'),
                    ),
                    'eng' => array(),
                    'ezdemo_site_admin' => array(),
                ),

                'imagemagick' => array(
                    'enabled' => true,
                    'path' => '/usr/bin/convert',
                ),
            ),
        );

        $exceptionType = InvalidArgumentException::class;

        $commonMockParameters = array(
            'getParameter' => array(
                'SiteSettingsDefaultAccess' => array('SiteSettings', 'DefaultAccess', null, null, 'eng'),
                'SiteAccessSettingsAvailableSiteAccessList' => array('SiteAccessSettings', 'AvailableSiteAccessList', null, null, array('eng', 'ezdemo_site', 'ezdemo_site_admin')),
                'FileSettingsVarDir' => array('FileSettings', 'VarDir', 'site.ini', 'eng', 'var/ezdemo_site'),
                'FileSettingsStorageDir' => array('FileSettings', 'StorageDir', 'site.ini', 'eng', 'storage'),
                'ImageMagickIsEnabled' => array('ImageMagick', 'IsEnabled', 'image.ini', 'eng', 'true'),
                'ImageMagickExecutablePath' => array('ImageMagick', 'ExecutablePath', 'image.ini', 'eng', '/usr/bin'),
                'ImageMagickExecutable' => array('ImageMagick', 'Executable', 'image.ini', 'eng', 'convert'),
                'Languages_eng' => array('RegionalSettings', 'SiteLanguageList', 'site.ini', 'eng', array('eng-GB')),
                'Languages_demo' => array('RegionalSettings', 'SiteLanguageList', 'site.ini', 'ezdemo_site', array('eng-GB')),
                'Languages_admin' => array('RegionalSettings', 'SiteLanguageList', 'site.ini', 'ezdemo_site_admin', array('eng-GB')),
                'SessionNameHandler_eng' => array('Session', 'SessionNameHandler', 'site.ini', 'eng', 'default'),
                'SessionNameHandler_demo' => array('Session', 'SessionNameHandler', 'site.ini', 'ezdemo_site', 'default'),
                'SessionNameHandler_admin' => array('Session', 'SessionNameHandler', 'site.ini', 'ezdemo_site_admin', 'default'),
                'SessionName' => array('Session', 'SessionNamePrefix', 'site.ini', null, 'eZSESSID'),
            ),
            'getGroup' => array(
                'SiteAccessSettings' => array(
                    'SiteAccessSettings', null, null,
                    array('MatchOrder' => 'uri', 'URIMatchType' => 'element', 'URIMatchElement' => 1),
                ),
                'DatabaseSettings' => array(
                    'DatabaseSettings', 'site.ini', 'eng',
                    array('DatabaseImplementation' => 'ezmysqli', 'Server' => 'localhost', 'User' => 'root', 'Password' => '', 'Database' => 'ezdemo'),
                ),
                'AliasSettings' => array(
                    'AliasSettings', 'image.ini', 'eng',
                    array('AliasList' => array('large', 'infoboximage')),
                ),
                'AliasSettings_demo' => array(
                    'AliasSettings', 'image.ini', 'ezdemo_site',
                    array('AliasList' => array('large', 'infoboximage')),
                ),
                'AliasSettings_admin' => array(
                    'AliasSettings', 'image.ini', 'ezdemo_site_admin',
                    array('AliasList' => array('large', 'infoboximage')),
                ),
                'large' => array(
                    'large', 'image.ini', 'eng',
                    array('Reference' => '', 'Filters' => array('geometry/scaledownonly=360;440')),
                ),
                'infoboximage' => array(
                    'infoboximage', 'image.ini', 'eng',
                    array('Reference' => '', 'Filters' => array('geometry/scalewidth=75', 'flatten')),
                ),
                'large_demo' => array(
                    'large', 'image.ini', 'ezdemo_site',
                    array('Reference' => '', 'Filters' => array('geometry/scaledownonly=360;440')),
                ),
                'infoboximage_demo' => array(
                    'infoboximage', 'image.ini', 'ezdemo_site',
                    array('Reference' => '', 'Filters' => array('geometry/scalewidth=75', 'flatten')),
                ),
                'large_admin' => array(
                    'large', 'image.ini', 'ezdemo_site_admin',
                    array('Reference' => '', 'Filters' => array('geometry/scaledownonly=360;440')),
                ),
                'infoboximage_admin' => array(
                    'infoboximage', 'image.ini', 'ezdemo_site_admin',
                    array('Reference' => '', 'Filters' => array('geometry/scalewidth=75', 'flatten')),
                ),
                'ImageMagick' => array(
                    'ImageMagick', 'image.ini', 'eng',
                    array('Filters' => array('geometry/scale=-geometry %1x%2', 'geometry/scalewidth=-geometry %1')),
                ),
            ),
        );

        $baseData = array('ezdemo', 'ezdemo_site_admin', $commonMockParameters, $commonResult);

        $data = array();
        $data[] = $baseData;

        // empty site list => invalid argument exception
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['SiteSettingsSiteList'] = array('SiteSettings', 'SiteList', null, null, array());
        $element[IDX_EXCEPTION] = $exceptionType;
        $data[] = $element;

        // imagemagick disabled
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['ImageMagickIsEnabled'] = array('ImageMagick', 'IsEnabled', 'eng', 'image.ini', 'false');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['imagemagick']['enabled'] = false;
        unset($element[IDX_EXPECTED_RESULT]['ezpublish']['imagemagick']['path']);
        unset($element[IDX_EXPECTED_RESULT]['ezpublish']['imagemagick']['filters']);
        $data[] = $element;

        // postgresql
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getGroup']['DatabaseSettings'][3]['DatabaseImplementation'] = 'ezpostgresql';
        $element[IDX_EXPECTED_RESULT]['doctrine']['dbal']['connections']['eng_repository_connection']['driver'] = 'pdo_pgsql';
        $data[] = $element;

        // host match, with map
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getGroup']['SiteAccessSettings'] = array(
            'SiteAccessSettings', null, null, array(
                'MatchOrder' => 'host',
                'HostMatchType' => 'map',
                'HostMatchMapItems' => array('site.com;eng', 'admin.site.com;ezdemo_site_admin'),
            ),
        );
        $element[IDX_EXPECTED_RESULT]['ezpublish']['siteaccess']['match'] = array(
            'Map\\Host' => array('site.com' => 'eng', 'admin.site.com' => 'ezdemo_site_admin'),
        );
        $data[] = $element;

        // host match, with map
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getGroup']['SiteAccessSettings'] = array(
            'SiteAccessSettings', null, null, array(
                'MatchOrder' => 'host',
                'HostMatchType' => 'map',
                'HostMatchMapItems' => array('site.com;eng', 'admin.site.com;ezdemo_site_admin'),
            ),
        );
        $element[IDX_EXPECTED_RESULT]['ezpublish']['siteaccess']['match'] = array(
            'Map\\Host' => array('site.com' => 'eng', 'admin.site.com' => 'ezdemo_site_admin'),
        );
        $data[] = $element;

        // customized storage dir
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['FileSettingsStorageDir'] = array('FileSettings', 'StorageDir', 'site.ini', 'eng', 'customstorage');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_group']['storage_dir'] = 'customstorage';
        $data[] = $element;

        // host match, with map
        $element = $baseData;
        $element[IDX_ADMIN_SITEACCESS] = 'winter';
        $element[IDX_EXCEPTION] = $exceptionType;
        $data[] = $element;

        // different alias list for ezdemo_site_admin
        // each siteaccess has its own variations list
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getGroup']['AliasSettings_admin'] = array(
            'AliasSettings', 'image.ini', 'ezdemo_site_admin',
            array(
                'AliasList' => array('large'),
            ),
        );
        unset($element[IDX_MOCK_PARAMETERS]['getGroup']['infoboximage_admin']);

        $data[] = $element;

        // different parameter for an alias in ezdemo_site_admin
        // each siteaccess has its own variations list
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getGroup']['large_admin'] = array(
            'large', 'image.ini', 'ezdemo_site_admin',
            array(
                'Reference' => '',
                'Filters' => array('geometry/scaledownonly=100;100'),
            ),
        );

        $data[] = $element;

        // several languages and same for all SA
        // still only a languages setting in ezdemo_group
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_eng'][4] = array('eng-GB', 'fre-FR');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_demo'][4] = array('eng-GB', 'fre-FR');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_admin'][4] = array('eng-GB', 'fre-FR');

        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_group']['languages'] = array('eng-GB', 'fre-FR');
        $data[] = $element;

        // several languages and same list for all SA but not the same order
        // no more languages setting in ezdemo_group, one by SA
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_eng'][4] = array('eng-GB', 'fre-FR');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_demo'][4] = array('fre-FR', 'eng-GB');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_admin'][4] = array('eng-GB', 'fre-FR');

        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['eng']['languages'] = array('eng-GB', 'fre-FR');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_site']['languages'] = array('fre-FR', 'eng-GB');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_site_admin']['languages'] = array('eng-GB', 'fre-FR');

        unset($element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_group']['languages']);
        $data[] = $element;

        // several languages and different lists for each SA
        // no more languages setting in ezdemo_group, one by SA
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_eng'][4] = array('eng-GB', 'fre-FR');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_demo'][4] = array('Entish', 'Valarin', 'Elvish');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['Languages_admin'][4] = array('Khuzdul', 'Sindarin');

        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['eng']['languages'] = array('eng-GB', 'fre-FR');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_site']['languages'] = array('Entish', 'Valarin', 'Elvish');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_site_admin']['languages'] = array('Khuzdul', 'Sindarin');

        unset($element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_group']['languages']);
        $data[] = $element;

        // session name
        $element = $baseData;
        $element[IDX_MOCK_PARAMETERS]['getParameter']['SessionNameHandler_eng'] = array('Session', 'SessionNameHandler', 'site.ini', 'eng', 'custom');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['SessionNameHandler_demo'] = array('Session', 'SessionNameHandler', 'site.ini', 'ezdemo_site', 'custom');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['SessionNamePerSiteAccess_eng'] = array('Session', 'SessionNamePerSiteAccess', 'site.ini', 'eng', 'enabled');
        $element[IDX_MOCK_PARAMETERS]['getParameter']['SessionNamePerSiteAccess_demo'] = array('Session', 'SessionNamePerSiteAccess', 'site.ini', 'ezdemo_site', 'disabled');
        $element[IDX_EXPECTED_RESULT]['ezpublish']['system']['ezdemo_site']['session'] = array('name' => 'eZSESSID');
        $data[] = $element;

        return $data;
    }

    /**
     * @param array $methodsToMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\eZ\Bundle\EzPublishLegacyBundle\DependencyInjection\Configuration\LegacyConfigResolver
     */
    protected function getLegacyConfigResolverMock(array $methodsToMock = array())
    {
        $mock = $this
            ->getMockBuilder(LegacyConfigResolver::class)
            ->setMethods(array_merge($methodsToMock, array('getParameter', 'getGroup')))
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    /**
     * @return \Closure
     */
    protected function getLegacyKernelMock()
    {
        $legacyKernelMock = $this->createMock(Kernel::class);
        $legacyKernelMock
            ->expects($this->any())
            ->method('runCallback')
            ->will($this->returnValue(ezpKernelResult::class));

        $closureMock = function () use ($legacyKernelMock) {
            return $legacyKernelMock;
        };

        return $closureMock;
    }
}
