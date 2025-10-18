<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\ErrorRenderer;

use Flaphl\Element\ErrorHandler\Debug;
use Flaphl\Element\ErrorHandler\Exception\FlattenException;

/**
 * CLI error renderer for command-line applications.
 * 
 * Renders exceptions with proper terminal formatting, colors (if supported),
 * and contextual information optimized for console output.
 */
class CliErrorRenderer implements ErrorRendererInterface
{
    private bool $debug;
    private bool $colors;

    public function __construct(bool $debug = false, ?bool $colors = null)
    {
        $this->debug = $debug;
        $this->colors = $colors ?? $this->supportsColors();
    }

    public function render(FlattenException $exception): string
    {
        $output = $this->renderHeader($exception);
        
        if ($this->debug) {
            $output .= $this->renderContext($exception);
            $output .= $this->renderTrace($exception);
            $output .= $this->renderMemoryInfo();
        }

        return $output;
    }

    private function renderHeader(FlattenException $exception): string
    {
        $output = "\n";
        $output .= $this->colorize('ERROR', 'red', true) . "\n";
        $output .= str_repeat('=', 60) . "\n\n";
        
        $output .= $this->colorize($exception->getClass(), 'yellow', true) . "\n";
        $output .= $exception->getMessage() . "\n\n";
        
        $output .= sprintf(
            "File: %s:%d\n\n",
            $this->colorize($exception->getFile(), 'cyan'),
            $exception->getLine()
        );

        return $output;
    }

    private function renderContext(FlattenException $exception): string
    {
        $context = Debug::getFileContext($exception->getFile(), $exception->getLine(), 5);
        
        if (empty($context)) {
            return '';
        }

        $output = $this->colorize('SOURCE CONTEXT', 'blue', true) . "\n";
        $output .= str_repeat('-', 40) . "\n";

        foreach ($context as $lineNum => $line) {
            $isCurrent = $lineNum === $exception->getLine();
            $marker = $isCurrent ? '>' : ' ';
            $lineColor = $isCurrent ? 'red' : 'white';
            
            $output .= sprintf(
                "%s %s | %s\n",
                $this->colorize($marker, 'red'),
                $this->colorize(sprintf('%4d', $lineNum), 'dark_gray'),
                $this->colorize($line, $lineColor)
            );
        }

        $output .= "\n";
        return $output;
    }

    private function renderTrace(FlattenException $exception): string
    {
        $output = $this->colorize('STACK TRACE', 'blue', true) . "\n";
        $output .= str_repeat('-', 40) . "\n";

        foreach ($exception->getTrace() as $i => $frame) {
            $function = isset($frame['class']) 
                ? $frame['class'] . $frame['type'] . $frame['function']
                : $frame['function'];
                
            $location = isset($frame['file']) 
                ? basename($frame['file']) . ':' . ($frame['line'] ?? '?')
                : '[internal]';

            $output .= sprintf(
                "%s %s\n    %s\n\n",
                $this->colorize(sprintf('#%d', $i), 'yellow'),
                $this->colorize($function . '()', 'green'),
                $this->colorize($location, 'cyan')
            );
        }

        return $output;
    }

    private function renderMemoryInfo(): string
    {
        $memory = Debug::getMemoryInfo();
        $execution = Debug::getExecutionTime();
        
        $output = $this->colorize('DEBUG INFO', 'blue', true) . "\n";
        $output .= str_repeat('-', 40) . "\n";
        $output .= sprintf("Memory Usage: %s\n", $memory['current_formatted']);
        $output .= sprintf("Peak Memory:  %s\n", $memory['peak_formatted']);
        $output .= sprintf("Execution:    %s\n\n", $execution['execution_formatted']);

        return $output;
    }

    private function colorize(string $text, string $color, bool $bold = false): string
    {
        if (!$this->colors) {
            return $text;
        }

        $colors = [
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'cyan' => 36,
            'white' => 37,
            'dark_gray' => 90,
        ];

        $colorCode = $colors[$color] ?? 37;
        $boldCode = $bold ? '1;' : '';

        return "\033[{$boldCode}{$colorCode}m{$text}\033[0m";
    }

    private function supportsColors(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Check for Windows Terminal or ConEmu
            return getenv('WT_SESSION') !== false || getenv('ConEmuPID') !== false;
        }

        // Unix-like systems
        $term = getenv('TERM');
        return $term && $term !== 'dumb' && function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
}