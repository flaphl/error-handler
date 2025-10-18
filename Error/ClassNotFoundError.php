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

/**
 * Represents a "Class not found" error.
 * 
 * This error occurs when PHP tries to use a class that hasn't been defined
 * or loaded, typically due to autoloading issues or missing dependencies.
 */
class ClassNotFoundError extends FatalError
{
    private string $className;

    public function __construct(
        string $className,
        string $message = '',
        string $filename = '',
        int $line = 0
    ) {
        $this->className = $className;
        
        if (empty($message)) {
            $message = sprintf('Class "%s" not found', $className);
        }

        parent::__construct($message, 0, E_ERROR, $filename, $line);
    }

    /**
     * Get the name of the missing class.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get suggestions for resolving this error.
     */
    public function getSuggestions(): array
    {
        return [
            'Check if the class name is spelled correctly',
            'Verify the namespace is correct',
            'Ensure the file containing the class is included or autoloaded',
            'Check if the required dependency is installed via Composer',
            'Verify the PSR-4 autoloading configuration in composer.json',
        ];
    }

    /**
     * Get enhanced error information.
     */
    public function getErrorInfo(): array
    {
        return array_merge(parent::getErrorInfo(), [
            'class_name' => $this->getClassName(),
            'suggestions' => $this->getSuggestions(),
        ]);
    }
}
