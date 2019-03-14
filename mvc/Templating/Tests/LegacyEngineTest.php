<?php
/**
 * File containing the LegacyEngineTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Tests;

use eZ\Publish\Core\MVC\Legacy\Templating\LegacyEngine;
use eZ\Publish\Core\MVC\Legacy\Templating\Converter\MultipleObjectConverter;
use PHPUnit\Framework\TestCase;

class LegacyEngineTest extends TestCase
{
    /**
     * @var \eZ\Publish\Core\MVC\Legacy\Templating\LegacyEngine
     */
    private $engine;

    protected function setUp()
    {
        parent::setUp();
        $this->engine = new LegacyEngine(
            static function () {
            },
            $this->createMock(MultipleObjectConverter::class)
        );
    }

    /**
     * @param $tplName
     * @param $expected
     *
     * @covers \eZ\Publish\Core\MVC\Legacy\Templating\LegacyEngine::supports
     *
     * @dataProvider supportTestProvider
     */
    public function testSupports($tplName, $expected)
    {
        $this->assertSame($expected, $this->engine->supports($tplName));
    }

    public function supportTestProvider()
    {
        return [
            ['design:foo/bar.tpl', true],
            ['file:some/path.tpl', true],
            ['unsupported.php', false],
            ['unsupported.tpl', false],
            ['design:unsupported.php', false],
            ['design:foo/bar.php', false],
            ['file:some/path.php', false],
        ];
    }
}
