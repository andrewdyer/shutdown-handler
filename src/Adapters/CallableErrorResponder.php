<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Adapters;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use YourVendor\YourPackage\Contracts\ErrorResponderInterface;

/**
 * Creates shutdown error responses by delegating to a callable.
 */
final readonly class CallableErrorResponder implements ErrorResponderInterface
{
    /**
     * Stores the callable that creates shutdown error responses.
     *
     * @var \Closure(ServerRequestInterface,Throwable,bool):ResponseInterface
     */
    private \Closure $responder;

    /**
     * Builds a callable-backed error responder adapter.
     *
     * @param  callable(ServerRequestInterface,Throwable,bool):ResponseInterface $responder The callable that creates shutdown error responses.
     * @return void                                                              Returns after assigning the responder callable.
     */
    public function __construct(callable $responder)
    {
        $this->responder = \Closure::fromCallable($responder);
    }

    /**
     * Creates an HTTP response for a shutdown exception.
     *
     * @param  ServerRequestInterface $request             The request active during shutdown handling.
     * @param  Throwable              $exception           The exception representing the fatal shutdown error.
     * @param  bool                   $displayErrorDetails Whether detailed error information should be included.
     * @return ResponseInterface      Returns the generated HTTP response.
     * @throws Throwable              When the wrapped responder callable fails.
     */
    public function createResponse(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        return ($this->responder)($request, $exception, $displayErrorDetails);
    }
}
