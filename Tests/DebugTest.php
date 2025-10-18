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

use Flaphl\Element\ErrorHandler\Debug;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    public function testCreateTrace(): void
    {
        $exception = new \Exception('Test exception');
        $trace = Debug::createTrace($exception);
        
        $this->assertIsArray($trace);
        $this->assertNotEmpty($trace);
        $this->assertArrayHasKey('class', $trace[0]);
        $this->assertArrayHasKey('message', $trace[0]);
        $this->assertEquals('Exception', $trace[0]['class']);
        $this->assertEquals('Test exception', $trace[0]['message']);
    }

    public function testFormatException(): void
    {
        $exception = new \Exception('Test exception');
        $formatted = Debug::formatException($exception);
        
        $this->assertStringContainsString('Exception: Test exception', $formatted);
        $this->assertStringContainsString('File:', $formatted);
        $this->assertStringContainsString('Stack trace:', $formatted);
    }

    public function testGetFileContext(): void
    {
        $context = Debug::getFileContext(__FILE__, __LINE__, 2);
        
        $this->assertIsArray($context);
        $this->assertNotEmpty($context);
        
        // Should contain lines around the current line
        $currentLine = __LINE__ - 4; // Adjust for the call above
        $this->assertArrayHasKey($currentLine, $context);
    }

    public function testGetVariableInfo(): void
    {
        $info = Debug::getVariableInfo('test string');
        $this->assertEquals('string', $info['type']);
        $this->assertEquals('"test string"', $info['value']);
        $this->assertEquals(11, $info['size']);

        $info = Debug::getVariableInfo([1, 2, 3]);
        $this->assertEquals('array', $info['type']);
        $this->assertEquals('array(3)', $info['value']);
        $this->assertEquals(3, $info['size']);

        $info = Debug::getVariableInfo(true);
        $this->assertEquals('boolean', $info['type']);
        $this->assertEquals('true', $info['value']);
    }

    public function testIsCli(): void
    {
        $this->assertTrue(Debug::isCli()); // PHPUnit runs in CLI mode
    }

    public function testGetMemoryInfo(): void
    {
        $memory = Debug::getMemoryInfo();
        
        $this->assertArrayHasKey('current', $memory);
        $this->assertArrayHasKey('current_formatted', $memory);
        $this->assertArrayHasKey('peak', $memory);
        $this->assertArrayHasKey('peak_formatted', $memory);
        $this->assertArrayHasKey('limit', $memory);
        
        $this->assertIsInt($memory['current']);
        $this->assertIsInt($memory['peak']);
        $this->assertStringContainsString('B', $memory['current_formatted']);
    }

    public function testFormatBytes(): void
    {
        $result = Debug::formatBytes(0);
        $this->assertEquals('0 B', $result);
        
        $result = Debug::formatBytes(1024);
        $this->assertEquals('1.00 KB', $result);
        
        $result = Debug::formatBytes(1024 * 1024);  
        $this->assertEquals('1.00 MB', $result);
        
        $result = Debug::formatBytes(1024 * 1024 * 1024);
        $this->assertEquals('1.00 GB', $result);
    }

    public function testGetExecutionTime(): void
    {
        $time = Debug::getExecutionTime();
        
        $this->assertArrayHasKey('start', $time);
        $this->assertArrayHasKey('current', $time);
        $this->assertArrayHasKey('execution', $time);
        $this->assertArrayHasKey('execution_formatted', $time);
        
        $this->assertIsFloat($time['execution']);
        $this->assertStringContainsString('seconds', $time['execution_formatted']);
    }

    public function testDump(): void
    {
        $result = Debug::dump(['test' => 'value'], true);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('array(1)', $result);
        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('value', $result);
    }
}