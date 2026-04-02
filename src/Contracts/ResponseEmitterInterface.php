<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Contracts;

use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Emits an HTTP response to the client.
 */
interface ResponseEmitterInterface
{
    /**
     * Emits the provided HTTP response.
     *
     * @param  ResponseInterface $response The response to send to the client.
     * @return void              Sends the response output.
     * @throws Throwable         When response emission fails.
     */
    public function emit(ResponseInterface $response): void;
}
