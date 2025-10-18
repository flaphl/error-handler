<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\Tests\Error;

use Flaphl\Element\ErrorHandler\Error\FatalError;
use PHPUnit\Framework\TestCase;

class FatalErrorTest extends TestCase
{
    public function testConstruction(): void
    {
        $error = new FatalError('Test message', 0, E_ERROR, '/path/file.php', 42);
        
        $this->assertEquals('Test message', $error->getMessage());
        $this->assertEquals(E_ERROR, $error->getSeverity());
        $this->assertEquals('/path/file.php', $error->getFile());
        $this->assertEquals(42, $error->getLine());
    }

    public function testGetSeverityName(): void
    {
        $error = new FatalError('Test', 0, E_ERROR);
        $this->assertEquals('Fatal Error', $error->getSeverityName());
        
        $error = new FatalError('Test', 0, E_WARNING);
        $this->assertEquals('Warning', $error->getSeverityName());
        
        $error = new FatalError('Test', 0, E_NOTICE);
        $this->assertEquals('Notice', $error->getSeverityName());
    }

    public function testIsFatal(): void
    {
        $fatalError = new FatalError('Test', 0, E_ERROR);
        $this->assertTrue($fatalError->isFatal());
        
        $warningError = new FatalError('Test', 0, E_WARNING);
        $this->assertFalse($warningError->isFatal());
    }

    public function testGetContext(): void
    {
        $context = ['var1' => 'value1', 'var2' => 'value2'];
        $error = new FatalError('Test', 0, E_ERROR, '', 0, $context);
        
        $this->assertEquals($context, $error->getContext());
    }

    public function testGetErrorInfo(): void
    {
        $error = new FatalError('Test message', 0, E_ERROR, '/path/file.php', 42);
        $info = $error->getErrorInfo();
        
        $this->assertArrayHasKey('message', $info);
        $this->assertArrayHasKey('file', $info);
        $this->assertArrayHasKey('line', $info);
        $this->assertArrayHasKey('severity', $info);
        $this->assertArrayHasKey('severity_name', $info);
        $this->assertArrayHasKey('is_fatal', $info);
        $this->assertArrayHasKey('context', $info);
        
        $this->assertEquals('Test message', $info['message']);
        $this->assertEquals('/path/file.php', $info['file']);
        $this->assertEquals(42, $info['line']);
        $this->assertEquals(E_ERROR, $info['severity']);
        $this->assertEquals('Fatal Error', $info['severity_name']);
        $this->assertTrue($info['is_fatal']);
    }
}