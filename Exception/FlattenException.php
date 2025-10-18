<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\Exception;

use Throwable;

/**
 * Flattened representation of an exception for serialization and rendering.
 * 
 * This class captures all relevant information from an exception in a format
 * that can be safely serialized, logged, or rendered without the original exception object.
 */
class FlattenException
{
    private string $message;
    private int $code;
    private ?FlattenException $previous = null;
    private array $trace = [];
    private string $file;
    private int $line;
    private string $class;
    private int $statusCode;
    private array $headers = [];

    private function __construct(
        string $message,
        int $code,
        string $file,
        int $line,
        string $class
    ) {
        $this->message = $message;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
        $this->class = $class;
        $this->statusCode = $this->determineStatusCode($class, $code);
    }

    /**
     * Create a FlattenException from a Throwable.
     */
    public static function createFromThrowable(Throwable $exception): self
    {
        $flattened = new self(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            get_class($exception)
        );

        $flattened->setTrace($exception->getTrace());

        if ($previous = $exception->getPrevious()) {
            $flattened->setPrevious(self::createFromThrowable($previous));
        }

        return $flattened;
    }

    /**
     * Get the exception message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the exception code.
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the previous exception.
     */
    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    /**
     * Set the previous exception.
     */
    public function setPrevious(?self $previous): self
    {
        $this->previous = $previous;
        return $this;
    }

    /**
     * Get the stack trace.
     */
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * Set the stack trace.
     */
    public function setTrace(array $trace): self
    {
        $this->trace = $this->sanitizeTrace($trace);
        return $this;
    }

    /**
     * Get the file where the exception occurred.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Get the line where the exception occurred.
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * Get the exception class name.
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set the HTTP status code.
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get HTTP headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set HTTP headers.
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Get the trace as a string.
     */
    public function getTraceAsString(): string
    {
        $output = '';
        
        foreach ($this->trace as $i => $frame) {
            $function = isset($frame['class']) 
                ? $frame['class'] . $frame['type'] . $frame['function']
                : $frame['function'];
                
            $file = $frame['file'] ?? '[internal function]';
            $line = $frame['line'] ?? '';
            
            $output .= sprintf(
                "#%d %s(%s): %s()\n",
                $i,
                $file,
                $line,
                $function
            );
        }

        return $output;
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'class' => $this->class,
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
            'trace' => $this->trace,
            'previous' => $this->previous?->toArray(),
        ];
    }

    /**
     * Create from array (for deserialization).
     */
    public static function fromArray(array $data): self
    {
        $flattened = new self(
            $data['message'] ?? '',
            $data['code'] ?? 0,
            $data['file'] ?? '',
            $data['line'] ?? 0,
            $data['class'] ?? 'Exception'
        );

        $flattened->setStatusCode($data['status_code'] ?? 500);
        $flattened->setHeaders($data['headers'] ?? []);
        $flattened->setTrace($data['trace'] ?? []);

        if (isset($data['previous']) && is_array($data['previous'])) {
            $flattened->setPrevious(self::fromArray($data['previous']));
        }

        return $flattened;
    }

    /**
     * Determine HTTP status code from exception class and code.
     */
    private function determineStatusCode(string $class, int $code): int
    {
        // Map common exception types to HTTP status codes
        $classMap = [
            'InvalidArgumentException' => 400,
            'UnexpectedValueException' => 400,
            'LogicException' => 400,
            'BadMethodCallException' => 400,
            'DomainException' => 400,
            'LengthException' => 400,
            'OutOfRangeException' => 400,
            'OutOfBoundsException' => 400,
            'OverflowException' => 400,
            'RangeException' => 400,
            'UnderflowException' => 400,
            'RuntimeException' => 500,
        ];

        $shortClass = substr(strrchr($class, '\\'), 1) ?: $class;
        
        if (isset($classMap[$shortClass])) {
            return $classMap[$shortClass];
        }

        // Use code if it's a valid HTTP status code
        if ($code >= 100 && $code < 600) {
            return $code;
        }

        return 500; // Default to Internal Server Error
    }

    /**
     * Sanitize trace array to remove sensitive information.
     */
    private function sanitizeTrace(array $trace): array
    {
        $sanitized = [];
        
        foreach ($trace as $frame) {
            $sanitizedFrame = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];

            // Remove sensitive arguments
            if (isset($frame['args'])) {
                $sanitizedFrame['args'] = $this->sanitizeArguments($frame['args']);
            }

            $sanitized[] = array_filter($sanitizedFrame, fn($value) => $value !== null);
        }

        return $sanitized;
    }

    /**
     * Sanitize function arguments to remove sensitive data.
     */
    private function sanitizeArguments(array $args): array
    {
        $sanitized = [];
        
        foreach ($args as $arg) {
            $sanitized[] = match (gettype($arg)) {
                'boolean' => $arg ? 'true' : 'false',
                'integer', 'double' => (string)$arg,
                'string' => strlen($arg) > 100 ? substr($arg, 0, 100) . '...' : $arg,
                'array' => sprintf('array(%d)', count($arg)),
                'object' => sprintf('object(%s)', get_class($arg)),
                'resource' => sprintf('resource(%s)', get_resource_type($arg)),
                'NULL' => 'null',
                default => 'unknown',
            };
        }

        return $sanitized;
    }
}
