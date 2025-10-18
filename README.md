# Flaphl ErrorHandler Element

**error abstractions pulled from Flaphl elements.**

## Installation

```bash
composer require flaphl/error-handler
```

## Features

- **Comprehensive Error Handling** - Handles PHP errors, exceptions, and fatal errors
- **Multiple Renderers** - HTML, JSON, and CLI output formats
- **PSR-3 Logging** - Integrates with any PSR-3 compatible logger
- **Debug Support** - Enhanced debugging information in development
- **Memory Management** - Handles out-of-memory scenarios gracefully
- **Error Classification** - Specialized error types with contextual information

## Basic Usage

```php
use Flaphl\Element\ErrorHandler\ErrorHandler;
use Flaphl\Element\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Monolog\Logger;

// Create error handler
$logger = new Logger('app');
$renderer = new HtmlErrorRenderer(debug: true);
$errorHandler = new ErrorHandler($logger, $renderer, debug: true);

// Register all handlers
$errorHandler->register();

// Now all errors, exceptions, and fatal errors will be handled
```

## Error Renderers

### HTML Renderer (Web Applications)

```php
use Flaphl\Element\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

$renderer = new HtmlErrorRenderer(debug: true);
$errorHandler = new ErrorHandler(renderer: $renderer);
```

### JSON Renderer (APIs)

```php
use Flaphl\Element\ErrorHandler\ErrorRenderer\JsonErrorRenderer;

$renderer = new JsonErrorRenderer(debug: true);
$errorHandler = new ErrorHandler(renderer: $renderer);
```

### CLI Renderer (Console Applications)

```php
use Flaphl\Element\ErrorHandler\ErrorRenderer\CliErrorRenderer;

$renderer = new CliErrorRenderer(debug: true, colors: true);
$errorHandler = new ErrorHandler(renderer: $renderer);
```