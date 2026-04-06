<?php

declare(strict_types=1);

namespace AndrewDyer\Slim\Error\Tests;

use AndrewDyer\Slim\Error\Contracts\ResponseEmitterInterface;
use AndrewDyer\Slim\Error\ShutdownHandler;
use AndrewDyer\Slim\Error\Tests\Support\TestErrorResponder;
use AndrewDyer\Slim\Error\Tests\Support\TestResponseEmitter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Unit tests for ShutdownHandler.
 */
final class ShutdownHandlerTest extends TestCase
{
    // ------------------------------------------------------------------------
    // Guard clause / early exit tests
    // ------------------------------------------------------------------------

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

    // ------------------------------------------------------------------------
    // Core behavior / happy path tests
    // ------------------------------------------------------------------------

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

    // ------------------------------------------------------------------------
    // Side effect / output handling tests
    // ------------------------------------------------------------------------

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

    // ------------------------------------------------------------------------
    // Failure propagation tests
    // ------------------------------------------------------------------------

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

    // ------------------------------------------------------------------------
    // API flexibility / last error resolver tests
    // ------------------------------------------------------------------------

    /**
     * Asserts that non-closure callables can be used as the last error resolver.
     */
    public function testAcceptsInvokableCallableAsLastErrorResolver(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $resolver = new class () {
            public function __invoke(): array
            {
                return ['type' => E_ERROR];
            }
        };

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            $resolver
        );

        $handler();

        self::assertSame(1, $responder->calls);
        self::assertSame(1, $emitter->calls);
    }

    /**
     * Asserts that array callables can be used as the last error resolver.
     */
    public function testAcceptsArrayCallableAsLastErrorResolver(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();
        $emitter = new TestResponseEmitter();

        $resolver = new class () {
            public function resolve(): array
            {
                return ['type' => E_ERROR];
            }
        };

        $handler = new ShutdownHandler(
            $request,
            $responder,
            $emitter,
            false,
            [$resolver, 'resolve']
        );

        $handler();

        self::assertSame(1, $responder->calls);
        self::assertSame(1, $emitter->calls);
    }
}
