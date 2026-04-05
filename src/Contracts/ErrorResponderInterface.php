<?php

declare(strict_types=1);

namespace AndrewDyer\Slim\Error\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Creates HTTP responses for shutdown exceptions.
 */
interface ErrorResponderInterface
{
    /**
     * Creates an HTTP response for a shutdown exception.
     *
     * @param  ServerRequestInterface $request             The request active during shutdown handling.
     * @param  Throwable              $exception           The exception representing the fatal shutdown error.
     * @param  bool                   $displayErrorDetails Whether detailed error information should be included.
     * @return ResponseInterface      Returns the generated HTTP response.
     * @throws Throwable              When response generation fails.
     */
    public function createResponse(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface;
}
