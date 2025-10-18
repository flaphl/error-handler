<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\Error;

use ErrorException;

/**
 * Represents a fatal PHP error that has been converted to an exception.
 * 
 * This class extends ErrorException to provide additional context
 * and methods for handling fatal errors in a consistent manner.
 */
class FatalError extends ErrorException
{
    private array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        int $severity = E_ERROR,
        string $filename = '',
        int $line = 0,
        array $context = []
    ) {
        parent::__construct($message, $code, $severity, $filename, $line);
        $this->context = $context;
    }

    /**
     * Get the error context (variables that existed in the scope of the error).
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a human-readable severity name.
     */
    public function getSeverityName(): string
    {
        return match ($this->getSeverity()) {
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',

            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            default => 'Unknown Error',
        };
    }

    /**
     * Check if this is a fatal error level.
     */
    public function isFatal(): bool
    {
        return in_array($this->getSeverity(), [
            E_ERROR,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ]);
    }

    /**
     * Get enhanced error information.
     */
    public function getErrorInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'severity' => $this->getSeverity(),
            'severity_name' => $this->getSeverityName(),
            'is_fatal' => $this->isFatal(),
            'context' => $this->getContext(),
        ];
    }
}
