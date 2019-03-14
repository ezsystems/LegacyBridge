<?php
/**
 * File containing the ConfigurationDumperTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Tests\SetupWizard;

use eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper;
use eZ\Publish\Core\MVC\Symfony\ConfigDumperInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ConfigurationDumperTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $fs;

    private $cacheDir;

    private $configDir;

    /**
     * @var array
     */
    private $envs;

    protected function setUp()
    {
        parent::setUp();
        $this->fs = $this->createMock(Filesystem::class);
        $this->cacheDir = __DIR__ . '/cache';
        $this->configDir = __DIR__ . '/config';
        @mkdir($this->configDir);
        $this->envs = ['dev', 'prod'];
    }

    protected function tearDown()
    {
        array_map('unlink', glob("$this->configDir/*.yml"));
        rmdir($this->configDir);
        parent::tearDown();
    }

    private function expectsCacheClear()
    {
        $this->fs
            ->expects($this->once())
            ->method('rename')
            ->with($this->cacheDir, "{$this->cacheDir}_old");

        $this->fs
            ->expects($this->once())
            ->method('remove')
            ->with("{$this->cacheDir}_old");
    }

    public function dumpProvider()
    {
        return [
            [
                [
                    'foo' => 'bar',
                    'baz' => null,
                    'flag' => true,
                    'myArray' => [1, 2, 3],
                    'myHash' => ['this' => 'that', 'these' => 'those'],
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'flag' => true,
                    'someArray' => [1, 2, 3],
                    'nestedArray' => [
                        'anotherArray' => ['one', 'two', 'three'],
                        'anotherHash' => [
                            'someKey' => 123,
                            'anotherFlag' => false,
                            'nullValue' => null,
                            'emptyArray' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function assertConfigFileValid(array $configArray)
    {
        $configFile = "$this->configDir/ezpublish.yml";
        $this->assertFileExists($configFile);
        $this->assertEquals($configArray, Yaml::parse(file_get_contents($configFile)));
    }

    private function assertEnvConfigFilesValid(array $configArray = [])
    {
        $configArray = array_merge_recursive(
            $configArray,
            [
                'imports' => [['resource' => 'ezpublish.yml']],
            ]
        );

        foreach ($this->envs as $env) {
            $configFile = "$this->configDir/ezpublish_$env.yml";
            $this->assertFileExists($configFile);
            $this->assertEquals($configArray, Yaml::parse(file_get_contents($configFile)));
        }
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::__construct
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::dump
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::clearCache
     *
     * @dataProvider dumpProvider
     */
    public function testDumpNoPreviousFile(array $configArray)
    {
        $this->fs
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(false));
        $this->expectsCacheClear();

        $dumper = new ConfigurationDumper($this->fs, $this->envs, __DIR__, $this->cacheDir);
        $dumper->dump($configArray);
        $this->assertConfigFileValid($configArray);
        $this->assertEnvConfigFilesValid();
    }

    /**
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::__construct
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::dump
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::backupConfigFile
     * @covers \eZ\Bundle\EzPublishLegacyBundle\SetupWizard\ConfigurationDumper::clearCache
     *
     * @dataProvider dumpProvider
     */
    public function testDumpBackupFile(array $configArray)
    {
        $this->fs
            ->expects($this->any())
            ->method('exists')
            ->will(($this->returnValue(true)));
        $this->expectBackup();
        $this->expectsCacheClear();

        $dumper = new ConfigurationDumper($this->fs, $this->envs, __DIR__, $this->cacheDir);
        $dumper->dump($configArray, ConfigDumperInterface::OPT_BACKUP_CONFIG);
        $this->assertConfigFileValid($configArray);
        $this->assertEnvConfigFilesValid();
    }

    private function expectBackup()
    {
        $this->fs
            ->expects($this->exactly(\count($this->envs) + 1))
            ->method('copy')
            ->with(
                $this->logicalAnd(
                    $this->stringStartsWith("$this->configDir/ezpublish"),
                    $this->stringEndsWith('.yml')
                ),
                $this->logicalAnd(
                    $this->stringStartsWith("$this->configDir/ezpublish"),
                    $this->stringContains('.yml-' . date('Y-m-d_'))
                )
            );
    }
}
