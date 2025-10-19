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
 * HTML error renderer for web applications.
 * 
 * Renders exceptions as formatted HTML with optional debug information,
 * stack traces, and context for development environments.
 */
class HtmlErrorRenderer implements ErrorRendererInterface
{
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function render(FlattenException $exception): string
    {
        if ($this->debug) {
            return $this->renderDebug($exception);
        }

        return $this->renderProduction($exception);
    }

    private function renderProduction(FlattenException $exception): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 50px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; margin: 0 0 20px; font-size: 28px; }
        p { color: #6c757d; line-height: 1.6; margin: 0 0 20px; }
        .error-code { font-size: 18px; font-weight: 600; color: #495057; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Server Error</h1>
        <div class="error-code">Error {
            $exception->getStatusCode()
        }</div>
        <p>We're sorry, but something went wrong on our end. Please try again later.</p>
    </div>
</body>
</html>
HTML;
    }

    private function renderDebug(FlattenException $exception): string
    {
        $trace = $this->renderTrace($exception);
        $context = $this->renderContext($exception);
        $memory = Debug::getMemoryInfo();

        // Escape class and message for HTML title and header to avoid reflected XSS in debug output.
        $title = $this->escape($exception->getClass() . ': ' . $exception->getMessage());
        $escapedClass = $this->escape($exception->getClass());

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <style>
        body { font-family: Monaco, Consolas, monospace; margin: 0; padding: 20px; background: #1e1e1e; color: #d4d4d4; font-size: 13px; line-height: 1.4; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #252526; padding: 20px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #f14c4c; }
        .exception-class { color: #f14c4c; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .exception-message { color: #fff; font-size: 16px; margin-bottom: 15px; word-wrap: break-word; }
        .exception-location { color: #9cdcfe; }
        .section { background: #252526; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
        .section-title { color: #4ec9b0; font-size: 16px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #3c3c3c; padding-bottom: 5px; }
        .trace-item { margin-bottom: 15px; padding: 10px; background: #2d2d30; border-radius: 4px; }
        .trace-number { color: #f14c4c; font-weight: bold; }
        .trace-location { color: #9cdcfe; }
        .trace-function { color: #dcdcaa; }
        .context-line { padding: 2px 0; }
        .context-line-number { color: #858585; width: 40px; display: inline-block; text-align: right; margin-right: 15px; }
        .context-line-current { background: #3c2929; border-left: 3px solid #f14c4c; padding-left: 10px; }
        .context-code { color: #d4d4d4; }
        .memory-info { color: #9cdcfe; margin-top: 10px; }
        pre { margin: 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="exception-class">{$escapedClass}</div>
            <div class="exception-message">{$this->escape($exception->getMessage())}</div>
            <div class="exception-location">
                in {$this->escape($exception->getFile())} line {$exception->getLine()}
            </div>
            <div class="memory-info">
                Memory: {$memory['current_formatted']} (Peak: {$memory['peak_formatted']})
            </div>
        </div>
        
        {$context}
        {$trace}
    </div>
</body>
</html>
HTML;
    }

    private function renderTrace(FlattenException $exception): string
    {
        $html = '<div class="section"><div class="section-title">Stack Trace</div>';
        
        foreach ($exception->getTrace() as $i => $frame) {
            $function = isset($frame['class']) 
                ? $frame['class'] . $frame['type'] . $frame['function']
                : $frame['function'];
                
            $location = isset($frame['file']) 
                ? $this->escape($frame['file']) . ':' . ($frame['line'] ?? '?')
                : '[internal function]';
                
            $html .= <<<HTML
<div class="trace-item">
    <span class="trace-number">#{$i}</span>
    <span class="trace-location">{$location}</span><br>
    <span class="trace-function">{$this->escape($function)}()</span>
</div>
HTML;
        }
        
        $html .= '</div>';
        return $html;
    }

    private function renderContext(FlattenException $exception): string
    {
        $context = Debug::getFileContext($exception->getFile(), $exception->getLine(), 8);
        
        if (empty($context)) {
            return '';
        }
        
        $html = '<div class="section"><div class="section-title">Source Context</div><pre>';
        
        foreach ($context as $lineNum => $line) {
            $isCurrent = $lineNum === $exception->getLine();
            $class = $isCurrent ? 'context-line context-line-current' : 'context-line';
            
            $html .= sprintf(
                '<div class="%s"><span class="context-line-number">%%d</span><span class="context-code">%%s</span></div>',
                $class,
                $lineNum,
                $this->escape($line)
            );
        }
        
        $html .= '</pre></div>';
        return $html;
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}