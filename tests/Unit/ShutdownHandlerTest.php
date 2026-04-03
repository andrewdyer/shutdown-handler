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
            static fn (): array => [] // malformed
        );

        $handler();

        self::assertSame(0, $responder->calls);
        self::assertSame(0, $emitter->calls);
    }

    /**
     * Asserts that output buffers are cleared before response emission.
     */
    public function testClearsOutputBufferBeforeEmitting(): void
    {
        ob_start();
        echo 'garbage output';

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

        $handler();

        self::assertSame('', ob_get_contents());
        ob_end_clean();
    }

    /**
     * Asserts that exceptions from the response emitter are propagated.
     */
    public function testBubblesExceptionFromEmitter(): void
    {
        $this->expectException(\RuntimeException::class);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $responder = new TestErrorResponder();

        /**
         * Processes response emission with an intentional failure.
         */
        $emitter = new class () implements ResponseEmitterInterface {
            /**
             * Processes HTTP response emission.
             *
             * @param  ResponseInterface $response Receives the response to emit.
             * @return void              Returns after throwing an exception for test coverage.
             * @throws \RuntimeException Always thrown to simulate emitter failure.
             */
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
