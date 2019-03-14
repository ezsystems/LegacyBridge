<?php
/**
 * File containing the LoaderStringTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Tests\Twig;

use eZ\Publish\Core\MVC\Legacy\Templating\Twig\LoaderString;
use PHPUnit\Framework\TestCase;
use Twig_Source;

class LoaderStringTest extends TestCase
{
    public function testGetSource()
    {
        $loaderString = new LoaderString();
        $this->assertSame('foo', $loaderString->getSource('foo'));
    }

    public function testGetSourceContext()
    {
        $loaderString = new LoaderString();
        $this->assertEquals(new Twig_Source('foo', 'foo'), $loaderString->getSourceContext('foo'));
    }

    public function testGetCacheKey()
    {
        $loaderString = new LoaderString();
        $this->assertSame('foo', $loaderString->getCacheKey('foo'));
    }

    public function testIsFresh()
    {
        $loaderString = new LoaderString();
        $this->assertTrue($loaderString->isFresh('foo', time()));
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists($name, $expectedResult)
    {
        $loaderString = new LoaderString();
        $this->assertSame($expectedResult, $loaderString->exists($name));
    }

    public function existsProvider()
    {
        return [
            ['foo.html.twig', false],
            ['foo/bar/baz.txt.twig', false],
            ['SOMETHING.HTML.tWiG', false],
            ['foo', true],
            ['Hey, I love twig', true],
            ['Hey, I love Twig', true],
        ];
    }
}
