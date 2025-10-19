# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-10-19

### Security
- **Fixed XSS vulnerability in HtmlErrorRenderer**: Exception class names are now properly escaped in HTML title and debug header
- **Fixed format string vulnerability**: Corrected sprintf format specifiers in context rendering to prevent format string attacks

### Added
- Test coverage for XSS prevention in HtmlErrorRenderer with reflected content validation

### Fixed
- Exception class name now properly escaped to prevent reflected XSS in debug output
- sprintf format string now uses `%%d` and `%%s` to avoid format string vulnerabilities

## [1.0.0] - 2025-10-18

### Added
- Initial implementation of ErrorHandler with comprehensive error management
- Multiple error renderers: HTML, JSON, and CLI with debug support
- Specialized error types: FatalError, ClassNotFoundError, OutOfMemoryError, UndefinedFunctionError, UndefinedMethodError
- FlattenException for safe exception serialization and rendering
- Debug utilities with enhanced variable dumping and context extraction
- Memory management for fatal error scenarios with reserved memory
- PSR-3 logger integration with contextual error information
- Cross-platform color support in CLI renderer
- Intelligent error suggestions based on error type and context
- HTTP status code mapping for web applications
- Comprehensive test suite with PHPUnit configuration

### Features
- **Error Classification**: Specialized error types with contextual information and suggestions
- **Multi-Format Rendering**: HTML (production/debug), JSON (API), CLI (console) output formats
- **Memory Safety**: Reserved memory handling for out-of-memory scenarios
- **Debug Enhancement**: File context, stack traces, memory info, execution timing
- **PSR Compliance**: PSR-3 logging integration with proper error level mapping
- **Framework Ready**: Easy integration with dependency injection containers
- **Production Safe**: Secure error rendering that hides sensitive information in production