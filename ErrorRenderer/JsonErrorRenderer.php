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

use Flaphl\Element\ErrorHandler\Exception\FlattenException;

/**
 * JSON error renderer for API applications.
 * 
 * Renders exceptions as JSON responses suitable for API consumption,
 * with optional debug information for development environments.
 */
class JsonErrorRenderer implements ErrorRendererInterface
{
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function render(FlattenException $exception): string
    {
        $data = [
            'error' => [
                'type' => 'exception',
                'class' => $exception->getClass(),
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode(),
            ]
        ];

        if ($this->debug) {
            $data['error']['file'] = $exception->getFile();
            $data['error']['line'] = $exception->getLine();
            $data['error']['trace'] = $this->formatTrace($exception->getTrace());
            
            if ($previous = $exception->getPrevious()) {
                $data['error']['previous'] = $this->formatPrevious($previous);
            }
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function formatTrace(array $trace): array
    {
        return array_map(function ($frame, $index) {
            $formatted = [
                'step' => $index,
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
            ];

            if (isset($frame['class'])) {
                $formatted['class'] = $frame['class'];
                $formatted['type'] = $frame['type'] ?? '::';
            }

            return $formatted;
        }, $trace, array_keys($trace));
    }

    private function formatPrevious(FlattenException $exception): array
    {
        $data = [
            'class' => $exception->getClass(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($previous = $exception->getPrevious()) {
            $data['previous'] = $this->formatPrevious($previous);
        }

        return $data;
    }
}