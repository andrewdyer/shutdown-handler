<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use YourVendor\YourPackage\Contracts\ErrorResponderInterface;

/**
 * Test error responder stub used by unit tests.
 */
final class TestErrorResponder implements ErrorResponderInterface
{
    /**
     * Stores the number of times a response was requested.
     *
     * @var int
     */
    public int $calls = 0;

    /**
     * Stores the most recent exception provided by the handler.
     *
     * @var \Throwable|null
     */
    public ?\Throwable $lastException = null;

    /**
     * Creates a generic 500 response and records invocation details.
     *
     * @param  ServerRequestInterface $request             The request associated with the shutdown event.
     * @param  \Throwable             $exception           The exception created by the shutdown handler.
     * @param  bool                   $displayErrorDetails Whether detailed errors were requested.
     * @return ResponseInterface      Returns a synthetic 500 response used by tests.
     */
    public function createResponse(
        ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        $this->calls++;
        $this->lastException = $exception;

        return (new ResponseFactory())->createResponse(500);
    }
}
