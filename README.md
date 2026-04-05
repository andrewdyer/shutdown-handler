# Slim Error

A composable error handling layer for Slim applications, enabling consistent and extensible handling of application failures.

## Introduction

This library integrates with [Slim Framework 4](https://www.slimframework.com/) and leverages [PSR-7 HTTP Message](https://www.php-fig.org/psr/psr-7/) standards to convert fatal shutdown failures into consistent HTTP responses. It achieves this through pluggable responder and emitter strategies, allowing applications to compose error handling logic that is both extensible and framework-aligned.

## Prerequisites

- **[PHP](https://www.php.net/)**: Version 8.3 or higher is required.
- **[Composer](https://getcomposer.org/)**: Dependency management tool for PHP.

## Installation

```bash
composer require andrewdyer/slim-error
```

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT) and is free for private or commercial projects.
