<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\Tests\Exception;

use Flaphl\Element\ErrorHandler\Exception\FlattenException;
use PHPUnit\Framework\TestCase;

class FlattenExceptionTest extends TestCase
{
    public function testCreateFromThrowable(): void
    {
        $original = new \Exception('Test message', 123);
        $flattened = FlattenException::createFromThrowable($original);
        
        $this->assertEquals('Test message', $flattened->getMessage());
        $this->assertEquals(123, $flattened->getCode());
        $this->assertEquals($original->getFile(), $flattened->getFile());
        $this->assertEquals($original->getLine(), $flattened->getLine());
        $this->assertEquals('Exception', $flattened->getClass());
    }

    public function testCreateFromThrowableWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $original = new \Exception('Main exception', 0, $previous);
        $flattened = FlattenException::createFromThrowable($original);
        
        $this->assertNotNull($flattened->getPrevious());
        $this->assertEquals('Previous exception', $flattened->getPrevious()->getMessage());
        $this->assertEquals('RuntimeException', $flattened->getPrevious()->getClass());
    }

    public function testStatusCodeMapping(): void
    {
        $invalidArg = new \InvalidArgumentException('Bad argument');
        $flattened = FlattenException::createFromThrowable($invalidArg);
        $this->assertEquals(400, $flattened->getStatusCode());
        
        $runtime = new \RuntimeException('Runtime error');
        $flattened = FlattenException::createFromThrowable($runtime);
        $this->assertEquals(500, $flattened->getStatusCode());
        
        $generic = new \Exception('Generic error', 404);
        $flattened = FlattenException::createFromThrowable($generic);
        $this->assertEquals(404, $flattened->getStatusCode());
    }

    public function testTrace(): void
    {
        $exception = new \Exception('Test');
        $flattened = FlattenException::createFromThrowable($exception);
        
        $trace = $flattened->getTrace();
        $this->assertIsArray($trace);
        $this->assertNotEmpty($trace);
        
        $traceString = $flattened->getTraceAsString();
        $this->assertIsString($traceString);
        $this->assertStringContainsString('#0', $traceString);
    }

    public function testToArrayAndFromArray(): void
    {
        $original = new \Exception('Test message', 123);
        $flattened = FlattenException::createFromThrowable($original);
        $flattened->setStatusCode(404);
        $flattened->setHeaders(['Content-Type' => 'application/json']);
        
        $array = $flattened->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('Test message', $array['message']);
        $this->assertEquals(123, $array['code']);
        $this->assertEquals(404, $array['status_code']);
        $this->assertEquals(['Content-Type' => 'application/json'], $array['headers']);
        
        $reconstructed = FlattenException::fromArray($array);
        $this->assertEquals($flattened->getMessage(), $reconstructed->getMessage());
        $this->assertEquals($flattened->getCode(), $reconstructed->getCode());
        $this->assertEquals($flattened->getStatusCode(), $reconstructed->getStatusCode());
        $this->assertEquals($flattened->getHeaders(), $reconstructed->getHeaders());
    }

    public function testSettersAndGetters(): void
    {
        $flattened = FlattenException::createFromThrowable(new \Exception('Test'));
        
        $previous = FlattenException::createFromThrowable(new \RuntimeException('Previous'));
        $flattened->setPrevious($previous);
        $this->assertSame($previous, $flattened->getPrevious());
        
        $flattened->setStatusCode(418);
        $this->assertEquals(418, $flattened->getStatusCode());
        
        $headers = ['X-Custom' => 'value'];
        $flattened->setHeaders($headers);
        $this->assertEquals($headers, $flattened->getHeaders());
        
        $trace = [['file' => 'test.php', 'line' => 42, 'function' => 'testFunc']];
        $flattened->setTrace($trace);
        $this->assertCount(1, $flattened->getTrace());
    }
}