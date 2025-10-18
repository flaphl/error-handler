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
 * Represents an "Out of memory" error.
 * 
 * This error occurs when PHP runs out of available memory,
 * usually due to memory-intensive operations or memory leaks.
 */
class OutOfMemoryError extends FatalError
{
    private int $memoryLimit;
    private int $memoryUsage;

    public function __construct(
        string $message = '',
        string $filename = '',
        int $line = 0,
        ?int $memoryLimit = null,
        ?int $memoryUsage = null
    ) {
        if (empty($message)) {
            $message = 'Fatal error: Allowed memory size exhausted';
        }

        $this->memoryLimit = $memoryLimit ?? $this->parseMemoryLimit();
        $this->memoryUsage = $memoryUsage ?? memory_get_usage(true);

        parent::__construct($message, 0, E_ERROR, $filename, $line);
    }

    /**
     * Get the memory limit in bytes.
     */
    public function getMemoryLimit(): int
    {
        return $this->memoryLimit;
    }

    /**
     * Get the current memory usage in bytes.
     */
    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    /**
     * Get the memory limit formatted for humans.
     */
    public function getFormattedMemoryLimit(): string
    {
        return $this->formatBytes($this->memoryLimit);
    }

    /**
     * Get the memory usage formatted for humans.
     */
    public function getFormattedMemoryUsage(): string
    {
        return $this->formatBytes($this->memoryUsage);
    }

    /**
     * Get suggestions for resolving this error.
     */
    public function getSuggestions(): array
    {
        return [
            'Increase the memory_limit in php.ini',
            'Optimize your code to use less memory',
            'Process large datasets in smaller chunks',
            'Use generators instead of loading all data into arrays',
            'Unset large variables when no longer needed',
            'Check for memory leaks in loops',
            'Consider using external storage for large data sets',
        ];
    }

    /**
     * Get enhanced error information.
     */
    public function getErrorInfo(): array
    {
        return array_merge(parent::getErrorInfo(), [
            'memory_limit' => $this->getMemoryLimit(),
            'memory_usage' => $this->getMemoryUsage(),
            'memory_limit_formatted' => $this->getFormattedMemoryLimit(),
            'memory_usage_formatted' => $this->getFormattedMemoryUsage(),
            'suggestions' => $this->getSuggestions(),
        ]);
    }

    /**
     * Parse the memory_limit INI setting to bytes.
     */
    private function parseMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $units = ['b' => 1, 'k' => 1024, 'm' => 1048576, 'g' => 1073741824];
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return isset($units[$unit]) ? $value * $units[$unit] : $value;
    }

    /**
     * Format bytes into human readable format.
     */
    private function formatBytes(int $size, int $precision = 2): string
    {
        if ($size === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);
        
        return sprintf(
            '%.' . $precision . 'f %s',
            pow(1024, $base - floor($base)),
            $units[floor($base)]
        );
    }
}
