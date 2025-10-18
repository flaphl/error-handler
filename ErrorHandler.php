<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler;

use Flaphl\Element\ErrorHandler\Error\FatalError;
use Flaphl\Element\ErrorHandler\Exception\FlattenException;
use Flaphl\Element\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Central error handler for PHP errors, exceptions, and fatal errors.
 * 
 * Provides comprehensive error handling with logging, rendering, and graceful degradation.
 * Handles both recoverable errors and fatal shutdown scenarios.
 */
class ErrorHandler
{
    private LoggerInterface $logger;
    private ErrorRendererInterface $renderer;
    private bool $debug = false;
    private array $errorLevels = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING', 
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',

        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED',
    ];
    
    private array $reservedMemory;
    private bool $isHandlingFatalError = false;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?ErrorRendererInterface $renderer = null,
        bool $debug = false
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->renderer = $renderer ?? new class implements ErrorRendererInterface {
            public function render(FlattenException $exception): string {
                return sprintf("Error: %s\n", $exception->getMessage());
            }
        };
        $this->debug = $debug;
        
        // Reserve memory for fatal error handling
        $this->reservedMemory = array_fill(0, 1024 * 10, 'x');
    }

    /**
     * Register all error handlers.
     */
    public function register(): self
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
        
        return $this;
    }

    /**
     * Unregister all error handlers.
     */
    public function unregister(): self
    {
        restore_error_handler();
        restore_exception_handler();
        
        return $this;
    }

    /**
     * Handle PHP errors.
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $levelName = $this->errorLevels[$level] ?? 'UNKNOWN';
        
        $context = [
            'level' => $level,
            'level_name' => $levelName,
            'file' => $file,
            'line' => $line,
        ];

        // Log the error
        match ($level) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR => 
                $this->logger->error($message, $context),
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 
                $this->logger->warning($message, $context),
            E_NOTICE, E_USER_NOTICE => 
                $this->logger->notice($message, $context),
            E_DEPRECATED, E_USER_DEPRECATED => 
                $this->logger->info($message, $context),
            default => $this->logger->debug($message, $context),
        };

        // Convert to exception for fatal errors
        if (in_array($level, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            throw new FatalError($message, 0, $level, $file, $line);
        }

        return !$this->debug;
    }

    /**
     * Handle uncaught exceptions.
     */
    public function handleException(Throwable $exception): void
    {
        $flattenException = FlattenException::createFromThrowable($exception);
        
        // Log the exception
        $this->logger->critical($exception->getMessage(), [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Render the exception
        try {
            $output = $this->renderer->render($flattenException);
            
            if (PHP_SAPI !== 'cli') {
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: text/html; charset=utf-8');
                }
            }
            
            echo $output;
        } catch (Throwable $renderException) {
            // Fallback if renderer fails
            echo "Internal Server Error\n";
            if ($this->debug) {
                echo "Original: " . $exception->getMessage() . "\n";
                echo "Render Error: " . $renderException->getMessage() . "\n";
            }
        }
    }

    /**
     * Handle fatal errors during shutdown.
     */
    public function handleFatalError(): void
    {
        // Free reserved memory
        $this->reservedMemory = [];
        
        if ($this->isHandlingFatalError) {
            return;
        }

        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            return;
        }

        $this->isHandlingFatalError = true;

        try {
            $exception = new FatalError(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            $this->handleException($exception);
        } catch (Throwable $e) {
            // Last resort - output basic error
            echo "Fatal Error: " . ($error['message'] ?? 'Unknown error') . "\n";
        }
    }

    /**
     * Set debug mode.
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Check if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Set error renderer.
     */
    public function setRenderer(ErrorRendererInterface $renderer): self
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * Get error renderer.
     */
    public function getRenderer(): ErrorRendererInterface
    {
        return $this->renderer;
    }

    /**
     * Set logger.
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get logger.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
