<?php
/**
 * File containing the TemplateTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Tests\Twig;

use eZ\Publish\Core\MVC\Legacy\Templating\Twig\Template;
use eZ\Publish\Core\MVC\Legacy\Templating\LegacyEngine;
use PHPUnit\Framework\TestCase;
use Twig_Environment;
use Twig_Loader_Array;

class TemplateTest extends TestCase
{
    const TEMPLATE_NAME = 'design:hello_world.tpl';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $legacyEngine;

    /**
     * @var \Twig_Environment
     */
    private $twigEnv;

    /**
     * @var \eZ\Publish\Core\MVC\Legacy\Templating\Twig\Template
     */
    private $template;

    protected function setUp()
    {
        parent::setUp();
        $this->legacyEngine = $this->createMock(LegacyEngine::class);
        $this->twigEnv = new Twig_Environment(new Twig_Loader_Array());
        $this->template = new Template(self::TEMPLATE_NAME, $this->twigEnv, $this->legacyEngine);
    }

    /**
     * @covers \eZ\Publish\Core\MVC\Legacy\Templating\Twig\Template::getTemplateName
     */
    public function testGetName()
    {
        $this->assertSame(self::TEMPLATE_NAME, $this->template->getTemplateName());
    }

    /**
     * @covers \eZ\Publish\Core\MVC\Legacy\Templating\Twig\Template::render
     */
    public function testRender()
    {
        $tplParams = ['foo' => 'bar', 'truc' => 'muche'];
        $this->legacyEngine
            ->expects($this->once())
            ->method('render')
            ->with(self::TEMPLATE_NAME, $tplParams);
        $this->template->render($tplParams);
    }
}
