<?php

declare(strict_types=1);

namespace AndrewDyer\ShutdownHandler\Tests\Support;

use AndrewDyer\ShutdownHandler\Contracts\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Response emitter test double for use in tests.
 */
final class TestResponseEmitter implements ResponseEmitterInterface
{
    /**
     * Stores the number of times a response was emitted.
     *
     * @var int
     */
    public int $calls = 0;

    /**
     * Processes response emission tracking.
     *
     * @param  ResponseInterface $response The response supplied for emission.
     * @return void              Returns after incrementing the emission call counter.
     */
    public function emit(ResponseInterface $response): void
    {
        $this->calls++;
    }
}
