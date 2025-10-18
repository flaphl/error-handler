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
 * Interface for rendering exceptions to various output formats.
 * 
 * Renderers convert exceptions into user-friendly output formats
 * such as HTML, JSON, XML, or plain text.
 */
interface ErrorRendererInterface
{
    /**
     * Render a flattened exception to string output.
     * 
     * @param FlattenException $exception The exception to render
     * @return string The rendered output
     */
    public function render(FlattenException $exception): string;
}
