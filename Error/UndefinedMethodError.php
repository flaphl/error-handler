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
 * Represents an "Undefined method" error.
 * 
 * This error occurs when PHP tries to call a method that doesn't exist
 * on an object, typically due to typos or calling methods on the wrong object type.
 */
class UndefinedMethodError extends FatalError
{
    private string $className;
    private string $methodName;

    public function __construct(
        string $className,
        string $methodName,
        string $message = '',
        string $filename = '',
        int $line = 0
    ) {
        $this->className = $className;
        $this->methodName = $methodName;
        
        if (empty($message)) {
            $message = sprintf('Call to undefined method %s::%s()', $className, $methodName);
        }

        parent::__construct($message, 0, E_ERROR, $filename, $line);
    }

    /**
     * Get the name of the class.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get the name of the undefined method.
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * Get suggestions for resolving this error.
     */
    public function getSuggestions(): array
    {
        $suggestions = [
            'Check if the method name is spelled correctly',
            'Verify the method exists in the class or its parent classes',
            'Check if the method is public and accessible from current scope',
            'Ensure you\'re calling the method on the correct object type',
        ];

        // Add similar method suggestions if the class exists
        if (class_exists($this->className)) {
            $similarMethods = $this->findSimilarMethods();
            if (!empty($similarMethods)) {
                $suggestions[] = 'Did you mean one of these methods: ' . implode(', ', $similarMethods);
            }
        }

        return $suggestions;
    }

    /**
     * Find methods with similar names in the class.
     */
    private function findSimilarMethods(): array
    {
        if (!class_exists($this->className)) {
            return [];
        }

        try {
            $reflection = new \ReflectionClass($this->className);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            $methodNames = array_map(fn($method) => $method->getName(), $methods);
            
            $similar = [];
            $targetMethod = strtolower($this->methodName);
            
            foreach ($methodNames as $methodName) {
                $methodLower = strtolower($methodName);
                
                // Check for similar spelling using Levenshtein distance
                if (levenshtein($targetMethod, $methodLower) <= 2) {
                    $similar[] = $methodName . '()';
                }
                
                // Check for partial matches
                if (str_contains($methodLower, $targetMethod) || str_contains($targetMethod, $methodLower)) {
                    if (!in_array($methodName . '()', $similar)) {
                        $similar[] = $methodName . '()';
                    }
                }
            }
            
            return array_slice($similar, 0, 5); // Limit to 5 suggestions
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Get enhanced error information.
     */
    public function getErrorInfo(): array
    {
        return array_merge(parent::getErrorInfo(), [
            'class_name' => $this->getClassName(),
            'method_name' => $this->getMethodName(),
            'suggestions' => $this->getSuggestions(),
        ]);
    }
}
