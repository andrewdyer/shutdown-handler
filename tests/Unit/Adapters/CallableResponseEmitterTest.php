<?php

declare(strict_types=1);

namespace AndrewDyer\ShutdownHandler\Tests\Unit\Adapters;

use AndrewDyer\ShutdownHandler\Adapters\CallableResponseEmitter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Unit tests for CallableResponseEmitter.
 */
final class CallableResponseEmitterTest extends TestCase
{
    /**
     * Asserts that emission is delegated to the provided callable.
     */
    public function testEmitDelegatesToCallable(): void
    {
        $calls = 0;
        $capturedResponse = null;
        $expectedResponse = (new ResponseFactory())->createResponse(204);

        $adapter = new CallableResponseEmitter(
            static function(ResponseInterface $response) use (&$calls, &$capturedResponse): void {
                $calls++;
                $capturedResponse = $response;
            }
        );

        $adapter->emit($expectedResponse);

        self::assertSame(1, $calls);
        self::assertSame($expectedResponse, $capturedResponse);
    }

    /**
     * Asserts that exceptions from the emitter callable are propagated.
     */
    public function testEmitBubblesExceptionFromCallable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Emitter failed');

        $adapter = new CallableResponseEmitter(
            static function(ResponseInterface $response): void {
                throw new \RuntimeException('Emitter failed');
            }
        );

        $adapter->emit((new ResponseFactory())->createResponse(500));
    }
}
