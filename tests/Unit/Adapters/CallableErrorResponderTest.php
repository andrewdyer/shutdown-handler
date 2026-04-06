<?php

declare(strict_types=1);

namespace AndrewDyer\ShutdownHandler\Tests\Unit\Adapters;

use AndrewDyer\ShutdownHandler\Adapters\CallableErrorResponder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Throwable;

/**
 * Unit tests for CallableErrorResponder.
 */
final class CallableErrorResponderTest extends TestCase
{
    /**
     * Asserts that response creation is delegated to the provided callable.
     */
    public function testCreateResponseDelegatesToCallable(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $exception = new RuntimeException('Fatal shutdown error');
        $displayErrorDetails = true;
        $expectedResponse = (new ResponseFactory())->createResponse(500);

        $calls = 0;
        $capturedRequest = null;
        $capturedException = null;
        $capturedDisplayErrorDetails = null;

        $adapter = new CallableErrorResponder(
            static function(
                ServerRequestInterface $request,
                Throwable $exception,
                bool $displayErrorDetails
            ) use (
                &$calls,
                &$capturedRequest,
                &$capturedException,
                &$capturedDisplayErrorDetails,
                $expectedResponse
            ): ResponseInterface {
                $calls++;
                $capturedRequest = $request;
                $capturedException = $exception;
                $capturedDisplayErrorDetails = $displayErrorDetails;

                return $expectedResponse;
            }
        );

        $actualResponse = $adapter->createResponse($request, $exception, $displayErrorDetails);

        self::assertSame(1, $calls);
        self::assertSame($request, $capturedRequest);
        self::assertSame($exception, $capturedException);
        self::assertTrue($capturedDisplayErrorDetails);
        self::assertSame($expectedResponse, $actualResponse);
    }

    /**
     * Asserts that exceptions from the responder callable are propagated.
     */
    public function testCreateResponseBubblesExceptionFromCallable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Responder failed');

        $adapter = new CallableErrorResponder(
            static function(
                ServerRequestInterface $request,
                Throwable $exception,
                bool $displayErrorDetails
            ): ResponseInterface {
                throw new RuntimeException('Responder failed');
            }
        );

        $adapter->createResponse(
            (new ServerRequestFactory())->createServerRequest('GET', '/'),
            new RuntimeException('boom'),
            false
        );
    }
}
