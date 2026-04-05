<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Adapters;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use YourVendor\YourPackage\Contracts\ResponseEmitterInterface;

/**
 * Processes shutdown response emission by delegating to a callable.
 */
final readonly class CallableResponseEmitter implements ResponseEmitterInterface
{
    /**
     * Stores the callable that emits shutdown responses.
     *
     * @var Closure(ResponseInterface):void
     */
    private Closure $emitter;

    /**
     * Builds a callable-backed response emitter adapter.
     *
     * @param  callable(ResponseInterface):void $emitter The callable that emits shutdown responses.
     * @return void                             Returns after assigning the emitter callable.
     */
    public function __construct(callable $emitter)
    {
        $this->emitter = Closure::fromCallable($emitter);
    }

    /**
     * Processes HTTP response emission through the configured callable.
     *
     * @param  ResponseInterface $response The response to emit.
     * @return void              Returns after delegating response emission.
     * @throws Throwable         When the wrapped emitter callable fails.
     */
    public function emit(ResponseInterface $response): void
    {
        ($this->emitter)($response);
    }
}
