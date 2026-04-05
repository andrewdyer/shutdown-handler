<?php

declare(strict_types=1);

namespace AndrewDyer\Slim\Error\Contracts;

use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Processes HTTP response emission for shutdown handling.
 */
interface ResponseEmitterInterface
{
    /**
     * Processes HTTP response emission.
     *
     * @param  ResponseInterface $response The response to emit.
     * @return void              Returns after response emission is delegated.
     * @throws Throwable         When response emission fails.
     */
    public function emit(ResponseInterface $response): void;
}
