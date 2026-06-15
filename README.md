# Shutdown Handler

A shutdown handler for Slim Framework applications that converts fatal errors into consistent HTTP responses.

[![Latest Stable Version](http://poser.pugx.org/andrewdyer/shutdown-handler/v?style=flat-square)](https://packagist.org/packages/andrewdyer/shutdown-handler)
[![Total Downloads](http://poser.pugx.org/andrewdyer/shutdown-handler/downloads?style=flat-square)](https://packagist.org/packages/andrewdyer/shutdown-handler)
[![License](http://poser.pugx.org/andrewdyer/shutdown-handler/license?style=flat-square)](https://packagist.org/packages/andrewdyer/shutdown-handler)
[![PHP Version Require](http://poser.pugx.org/andrewdyer/shutdown-handler/require/php?style=flat-square)](https://packagist.org/packages/andrewdyer/shutdown-handler)

## Introduction

This library provides a shutdown handler for [Slim Framework](https://www.slimframework.com/) applications, intercepting fatal errors that would otherwise result in unhandled crashes and transforming them into consistent HTTP responses. The handler is fully composable, allowing different responder and emitter strategies to be combined as required, and integrates seamlessly with existing Slim error handling and response emission workflows to maintain predictable application behaviour.

## Prerequisites

- **[PHP](https://www.php.net/)**: Version 8.3 or higher is required.
- **[Composer](https://getcomposer.org/)**: Dependency management tool for PHP.
- **[Slim Framework](https://www.slimframework.com/)**: Version 4 is required.

## Installation

```bash
composer require andrewdyer/shutdown-handler
```

## Getting Started

An error responder and response emitter are required before registering the shutdown handler.

### 1. Create an error responder

Error responders define how errors are transformed into HTTP responses.

```php
use AndrewDyer\ShutdownHandler\Contracts\ErrorResponderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class MyErrorResponder implements ErrorResponderInterface
{
    public function createResponse(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        // Custom response logic
    }
}
```

Alternatively, wrap existing logic using `CallableErrorResponder`:

```php
use AndrewDyer\ShutdownHandler\Adapters\CallableErrorResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

$errorResponder = new CallableErrorResponder(
    static fn (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface => $httpErrorHandler(
        $request,
        $exception,
        $displayErrorDetails,
        false,
        false
    )
);
```

The callable must accept a request, exception, and display flag, and return a PSR-7 response.

### 2. Create a response emitter

Response emitters are responsible for sending responses to the client.

```php
use AndrewDyer\ShutdownHandler\Contracts\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;

final class MyResponseEmitter implements ResponseEmitterInterface
{
    public function emit(ResponseInterface $response): void
    {
        // Custom emit logic
    }
}
```

Alternatively, wrap an existing emitter using `CallableResponseEmitter`:

```php
use AndrewDyer\ShutdownHandler\Adapters\CallableResponseEmitter;
use Psr\Http\Message\ResponseInterface;

$responseEmitter = new CallableResponseEmitter(
    static fn (ResponseInterface $response): void => $slimEmitter->emit($response)
);
```

The adapter wraps an existing emitter implementation.

## Usage

Register the shutdown handler to convert fatal errors into consistent HTTP responses:

```php
use AndrewDyer\ShutdownHandler\ShutdownHandler;

$shutdownHandler = new ShutdownHandler(
    $request,
    $errorResponder,
    $responseEmitter,
    $displayErrorDetails
);

register_shutdown_function($shutdownHandler);
```

The `$errorResponder` and `$responseEmitter` values can come from custom implementations or the callable adapters shown in Getting Started.

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT) and is free for private or commercial projects.
