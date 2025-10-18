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

use Flaphl\Element\ErrorHandler\Exception\FlattenException;
use Throwable;

/**
 * Debug utilities for development environments.
 * 
 * Provides enhanced error information, stack traces, and debugging helpers
 * for better development experience.
 */
class Debug
{
    /**
     * Create a detailed debug trace from an exception.
     */
    public static function createTrace(Throwable $exception): array
    {
        $trace = [];
        $current = $exception;

        do {
            $trace[] = [
                'class' => get_class($current),
                'message' => $current->getMessage(),
                'file' => $current->getFile(),
                'line' => $current->getLine(),
                'code' => $current->getCode(),
                'trace' => $current->getTrace(),
            ];
        } while ($current = $current->getPrevious());

        return $trace;
    }

    /**
     * Format exception for display.
     */
    public static function formatException(Throwable $exception): string
    {
        $flattened = FlattenException::createFromThrowable($exception);
        
        $output = sprintf(
            "%s: %s\n",
            $flattened->getClass(),
            $flattened->getMessage()
        );

        $output .= sprintf(
            "File: %s:%d\n\n",
            $flattened->getFile(),
            $flattened->getLine()
        );

        $output .= "Stack trace:\n";
        foreach ($flattened->getTrace() as $i => $frame) {
            $output .= sprintf(
                "#%d %s(%d): %s%s%s()\n",
                $i,
                $frame['file'] ?? '[internal function]',
                $frame['line'] ?? 0,
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function'] ?? ''
            );
        }

        return $output;
    }

    /**
     * Get context around a file line.
     */
    public static function getFileContext(string $file, int $line, int $context = 5): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $start = max(0, $line - $context - 1);
        $end = min(count($lines), $line + $context);

        $contextLines = [];
        for ($i = $start; $i < $end; $i++) {
            $contextLines[$i + 1] = $lines[$i];
        }

        return $contextLines;
    }

    /**
     * Get variable information for debugging.
     */
    public static function getVariableInfo(mixed $var): array
    {
        return [
            'type' => gettype($var),
            'value' => match (gettype($var)) {
                'boolean' => $var ? 'true' : 'false',
                'integer', 'double' => (string)$var,
                'string' => '"' . addcslashes($var, "\n\r\t\"\\") . '"',
                'array' => sprintf('array(%d)', count($var)),
                'object' => sprintf('object(%s)', get_class($var)),
                'resource' => sprintf('resource(%s)', get_resource_type($var)),
                'NULL' => 'null',
                default => 'unknown',
            },
            'size' => match (gettype($var)) {
                'string' => strlen($var),
                'array' => count($var),
                default => null,
            },
        ];
    }

    /**
     * Check if we're in CLI mode.
     */
    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Get memory usage information.
     */
    public static function getMemoryInfo(): array
    {
        return [
            'current' => memory_get_usage(true),
            'current_formatted' => self::formatBytes(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_formatted' => self::formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Format bytes into human readable format.
     */
    public static function formatBytes(int $size, int $precision = 2): string
    {
        if ($size === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);
        $unitIndex = floor($base);
        
        return sprintf(
            '%.' . $precision . 'f %s',
            $size / pow(1024, $unitIndex),
            $units[$unitIndex]
        );
    }

    /**
     * Get execution time information.
     */
    public static function getExecutionTime(): array
    {
        static $startTime = null;
        
        if ($startTime === null) {
            $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        }

        $currentTime = microtime(true);
        $executionTime = $currentTime - $startTime;

        return [
            'start' => $startTime,
            'current' => $currentTime,
            'execution' => $executionTime,
            'execution_formatted' => sprintf('%.4f seconds', $executionTime),
        ];
    }

    /**
     * Dump variable with enhanced formatting.
     */
    public static function dump(mixed $var, bool $return = false): ?string
    {
        $output = self::formatVariable($var);
        
        if ($return) {
            return $output;
        }

        if (self::isCli()) {
            echo $output . "\n";
        } else {
            echo '<pre>' . htmlspecialchars($output) . '</pre>';
        }

        return null;
    }

    /**
     * Format variable for display.
     */
    private static function formatVariable(mixed $var, int $depth = 0, array &$seen = []): string
    {
        $indent = str_repeat('  ', $depth);
        $info = self::getVariableInfo($var);

        if ($depth > 10) {
            return $indent . '*DEEP RECURSION*';
        }

        return match ($info['type']) {
            'boolean', 'integer', 'double', 'string', 'NULL' => $indent . $info['value'],
            'array' => self::formatArray($var, $depth, $seen),
            'object' => self::formatObject($var, $depth, $seen),
            'resource' => $indent . $info['value'],
            default => $indent . 'unknown type',
        };
    }

    /**
     * Format array for display.
     */
    private static function formatArray(array $array, int $depth, array &$seen): string
    {
        $indent = str_repeat('  ', $depth);
        $nextIndent = str_repeat('  ', $depth + 1);
        
        if (empty($array)) {
            return $indent . 'array(0) []';
        }

        $output = $indent . sprintf("array(%d) [\n", count($array));
        
        foreach ($array as $key => $value) {
            $keyStr = is_string($key) ? '"' . $key . '"' : $key;
            $output .= $nextIndent . $keyStr . ' => ';
            
            if (is_array($value) || is_object($value)) {
                $output .= "\n" . self::formatVariable($value, $depth + 2, $seen);
            } else {
                $valueInfo = self::getVariableInfo($value);
                $output .= $valueInfo['value'];
            }
            
            $output .= "\n";
        }
        
        $output .= $indent . ']';
        return $output;
    }

    /**
     * Format object for display.
     */
    private static function formatObject(object $object, int $depth, array &$seen): string
    {
        $indent = str_repeat('  ', $depth);
        $hash = spl_object_hash($object);
        
        if (isset($seen[$hash])) {
            return $indent . sprintf('*RECURSION* %s', get_class($object));
        }
        
        $seen[$hash] = true;
        $class = get_class($object);
        $output = $indent . sprintf("object(%s) {\n", $class);
        
        try {
            $reflection = new \ReflectionClass($object);
            $properties = $reflection->getProperties();
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $name = $property->getName();
                $value = $property->getValue($object);
                
                $visibility = $property->isPublic() ? 'public' : 
                            ($property->isProtected() ? 'protected' : 'private');
                
                $output .= str_repeat('  ', $depth + 1) . sprintf(
                    '%s $%s => %s' . "\n",
                    $visibility,
                    $name,
                    self::getVariableInfo($value)['value']
                );
            }
        } catch (\ReflectionException $e) {
            $output .= str_repeat('  ', $depth + 1) . '*REFLECTION ERROR*' . "\n";
        }
        
        unset($seen[$hash]);
        $output .= $indent . '}';
        return $output;
    }
}
