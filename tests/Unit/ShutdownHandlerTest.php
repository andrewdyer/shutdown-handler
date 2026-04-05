<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use YourVendor\YourPackage\Contracts\ResponseEmitterInterface;
use YourVendor\YourPackage\ShutdownHandler;
use YourVendor\YourPackage\Tests\Support\TestErrorResponder;
use YourVendor\YourPackage\Tests\Support\TestResponseEmitter;

/**
 * Unit tests for ShutdownHandler.
 */
final class ShutdownHandlerTest extends TestCase
{
    /**
     * Asserts that shutdown handling exits early when no last error is available.
     */
    public function testDoesNothingWhenNoLastError(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            static fn (): ?array => null
        );

        $handler();

        self::assertSame(0, $responder->calls);
        self::assertSame(0, $emitter->calls);
    }

    /**
     * Asserts that non-fatal errors are ignored during shutdown handling.
     */
    public function testIgnoresNonFatalErrors(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            true,
            static fn (): array => [
                'type' => E_USER_NOTICE,
                'message' => 'notice',
                'file' => 'example.php',
                'line' => 10,
            ]
        );

        $handler();

        self::assertSame(0, $responder->calls);
        self::assertSame(0, $emitter->calls);
    }

    /**
     * Asserts that fatal errors use a generic message when details are disabled.
     */
    public function testHandlesFatalErrorWithGenericMessageWhenDetailsDisabled(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            static fn (): array => [
                'type' => E_ERROR,
                'message' => 'boom',
                'file' => 'index.php',
                'line' => 99,
            ]
        );

        $handler();

        self::assertSame(1, $responder->calls);
        self::assertSame(1, $emitter->calls);
        self::assertSame(
            'An error while processing your request. Please try again later.',
            $responder->lastException?->getMessage()
        );
    }

    /**
     * Asserts that fatal errors include detailed context when details are enabled.
     */
    public function testHandlesFatalErrorWithDetailedMessageWhenDetailsEnabled(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            true,
            static fn (): array => [
                'type' => E_USER_ERROR,
                'message' => 'bad things happened',
                'file' => 'worker.php',
                'line' => 12,
            ]
        );

        $handler();

        self::assertSame(1, $responder->calls);
        self::assertSame(1, $emitter->calls);
        self::assertSame(
            'FATAL ERROR: bad things happened on line 12 in file worker.php.',
            $responder->lastException?->getMessage()
        );
    }

    /**
     * Asserts that malformed errors are ignored during shutdown handling.
     */
    public function testIgnoresMalformedError(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            static fn (): array => []
        );

        $handler();

        self::assertSame(0, $responder->calls);
        self::assertSame(0, $emitter->calls);
    }

    /**
     * Asserts that output buffers created after handler initialisation are cleared.
     */
    public function testClearsOnlyNewOutputBuffers(): void
    {
        $baselineLevel = ob_get_level();

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            static fn (): array => ['type' => E_ERROR]
        );

        ob_start();
        echo 'test buffer';

        $handler();

        self::assertSame($baselineLevel, ob_get_level());
    }

    /**
     * Asserts that exceptions from the response emitter are propagated.
     */
    public function testBubblesExceptionFromEmitter(): void
    {
        $this->expectException(\RuntimeException::class);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();

        $emitter = new class () implements ResponseEmitterInterface {
            public function emit(ResponseInterface $response): void
            {
                throw new \RuntimeException('Emitter failed');
            }
        };

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            static fn (): array => ['type' => E_ERROR]
        );

        $handler();
    }
}
