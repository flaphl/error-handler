<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\Tests;

use Flaphl\Element\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Flaphl\Element\ErrorHandler\Exception\FlattenException;
use PHPUnit\Framework\TestCase;

class HtmlErrorRendererTest extends TestCase
{
    public function testRenderDebugEscapesTitleAndClass(): void
    {
        // Create a FlattenException with HTML/script in the message and class-like name
        $original = new \Exception('<script>alert(1)</script>');
        $flattened = FlattenException::createFromThrowable($original);
        // artificially set a class name containing HTML (simulate a polluted class string)
        $reflection = new \ReflectionObject($flattened);
        $prop = $reflection->getProperty('class');
        $prop->setAccessible(true);
        $prop->setValue($flattened, '<img src=x onerror=alert(1)>');

        $renderer = new HtmlErrorRenderer(true);
        $output = $renderer->render($flattened);

        // The raw script and img string must not appear unescaped
        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $output);

        // The escaped equivalents should appear (ampersand+lt etc)
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $output);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $output);
    }
}