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

use Flaphl\Element\ErrorHandler\ErrorHandler;
use Flaphl\Element\ErrorHandler\Error\FatalError;
use Flaphl\Element\ErrorHandler\ErrorRenderer\CliErrorRenderer;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->errorHandler = new ErrorHandler(
            $this->logger,
            new CliErrorRenderer(true),
            true
        );
    }

    protected function tearDown(): void
    {
        $this->errorHandler->unregister();
    }

    public function testRegisterAndUnregister(): void
    {
        $handler = $this->errorHandler->register();
        $this->assertSame($this->errorHandler, $handler);
        
        $unregistered = $this->errorHandler->unregister();
        $this->assertSame($this->errorHandler, $unregistered);
    }

    public function testHandleError(): void
    {
        // Ensure error reporting includes warnings
        $oldReporting = error_reporting(E_ALL);
        
        try {
            $result = $this->errorHandler->handleError(
                E_WARNING,
                'Test warning',
                __FILE__,
                __LINE__
            );

            $this->assertFalse($result); // In debug mode, should return false
            $this->assertTrue($this->logger->hasWarning('Test warning'));
        } finally {
            error_reporting($oldReporting);
        }
    }

    public function testHandleFatalError(): void
    {
        $this->expectException(FatalError::class);
        $this->expectExceptionMessage('Test fatal error');

        $this->errorHandler->handleError(
            E_ERROR,
            'Test fatal error',
            __FILE__,
            __LINE__
        );
    }

    public function testHandleException(): void
    {
        $exception = new \Exception('Test exception');
        
        ob_start();
        $this->errorHandler->handleException($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Test exception', $output);
        $this->assertTrue($this->logger->hasCritical('Test exception'));
    }

    public function testDebugMode(): void
    {
        $this->assertTrue($this->errorHandler->isDebug());
        
        $this->errorHandler->setDebug(false);
        $this->assertFalse($this->errorHandler->isDebug());
    }

    public function testRendererGetterSetter(): void
    {
        $renderer = new CliErrorRenderer();
        $this->errorHandler->setRenderer($renderer);
        
        $this->assertSame($renderer, $this->errorHandler->getRenderer());
    }

    public function testLoggerGetterSetter(): void
    {
        $logger = new TestLogger();
        $this->errorHandler->setLogger($logger);
        
        $this->assertSame($logger, $this->errorHandler->getLogger());
    }
}