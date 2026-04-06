# Shutdown Handler

A shutdown handler for Slim applications that converts fatal errors into consistent HTTP responses through pluggable responder and emitter strategies.

## Introduction

This library provides a shutdown handler for Slim applications that converts fatal errors into consistent HTTP responses through pluggable responder and emitter strategies.

## Prerequisites

- **[PHP](https://www.php.net/)**: Version 8.3 or higher is required.
- **[Composer](https://getcomposer.org/)**: Dependency management tool for PHP.

## Installation

```bash
composer require andrewdyer/shutdown-handler
```

## Features

Slim Error provides a composable error handling layer for Slim applications. Features can be adopted independently depending on your needs.

### Pluggable Error Responders

Error responders define how errors are transformed into HTTP responses.

Implement the `ErrorResponderInterface` to customise response structure and content:

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

Alternatively, reuse existing logic with the `CallableErrorResponder` adapter:

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

The callable must match the `(ServerRequestInterface, Throwable, bool): ResponseInterface` signature.

### Flexible Response Emitters

Response emitters are responsible for sending responses to the client.

Implement the `ResponseEmitterInterface` to control emission behaviour:

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

Or wrap an existing emitter using the `CallableResponseEmitter` adapter:

```php
use AndrewDyer\ShutdownHandler\Adapters\CallableResponseEmitter;
use Psr\Http\Message\ResponseInterface;

$responseEmitter = new CallableResponseEmitter(
    static fn (ResponseInterface $response): void => $slimEmitter->emit($response)
);
```

The adapter wraps an existing emitter implementation.

### Shutdown Handling

The shutdown handler captures fatal errors during application execution and converts them into consistent HTTP responses.

Once a responder and emitter are configured, register the handler:

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

This ensures fatal errors are intercepted and transformed into structured responses.

#### Complete Slim integration

A typical Slim integration using callable adapters might look like:

```php
use AndrewDyer\ShutdownHandler\Adapters\CallableErrorResponder;use AndrewDyer\ShutdownHandler\Adapters\CallableResponseEmitter;use AndrewDyer\ShutdownHandler\ShutdownHandler;use Psr\Http\Message\ResponseInterface;use Psr\Http\Message\ServerRequestInterface;

$shutdownHandler = new ShutdownHandler(
    $request,
    new CallableErrorResponder(
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
    ),
    new CallableResponseEmitter(
        static fn (ResponseInterface $response): void => $responseEmitter->emit($response)
    ),
    $displayErrorDetails
);

register_shutdown_function($shutdownHandler);
```

This approach allows existing Slim error handling and response emission logic to be reused without modification.

### Composable Architecture

Slim Error is designed for composability. Responders and emitters can be freely combined to suit your application.

```php
$shutdownHandler = new ShutdownHandler(
    $request,
    $errorResponder,
    $responseEmitter,
    $displayErrorDetails
);
```

By decoupling responsibilities, the library enables flexible integration, easier testing, and long-term maintainability.

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT) and is free for private or commercial projects.
