<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Creates an HTTP response for a shutdown error condition.
 */
interface ErrorResponderInterface
{
    /**
     * Creates an HTTP response for a shutdown exception.
     *
     * @param  ServerRequestInterface $request             The request active during shutdown handling.
     * @param  Throwable              $exception           The exception representing the fatal shutdown error.
     * @param  bool                   $displayErrorDetails True to include detailed error information.
     * @return ResponseInterface      The generated HTTP response.
     * @throws Throwable              When response generation fails.
     */
    public function createResponse(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface;
}
