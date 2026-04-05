<?php

declare(strict_types=1);

namespace AndrewDyer\Slim\Error;

use AndrewDyer\Slim\Error\Contracts\ErrorResponderInterface;
use AndrewDyer\Slim\Error\Contracts\ResponseEmitterInterface;
use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Throwable;

/**
 * Processes fatal shutdown errors and emits the generated HTTP response.
 */
final class ShutdownHandler
{
    /**
     * Lists PHP error types treated as fatal during shutdown handling.
     *
     * @var list<int>
     */
    private const array FATAL_ERROR_TYPES = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
    ];

    /**
     * Stores the request active during shutdown handling.
     *
     * @var ServerRequestInterface
     */
    private readonly ServerRequestInterface $request;

    /**
     * Stores the collaborator that creates shutdown error responses.
     *
     * @var ErrorResponderInterface
     */
    private readonly ErrorResponderInterface $errorResponder;

    /**
     * Stores the collaborator that emits shutdown error responses.
     *
     * @var ResponseEmitterInterface
     */
    private readonly ResponseEmitterInterface $responseEmitter;

    /**
     * Indicates whether error details should be included in responses.
     *
     * @var bool
     */
    private readonly bool $displayErrorDetails;

    /**
     * Stores an optional resolver that returns the last PHP error.
     *
     * @var Closure|null
     */
    private readonly ?Closure $lastErrorResolver;

    /**
     * Stores the output buffer level at the time the handler is created.
     *
     * @var int
     */
    private readonly int $initialOutputBufferLevel;

    /**
     * Builds a shutdown handler with response collaborators.
     *
     * @param  ServerRequestInterface   $request             The request active during shutdown handling.
     * @param  ErrorResponderInterface  $errorResponder      The collaborator that creates shutdown error responses.
     * @param  ResponseEmitterInterface $responseEmitter     The collaborator that emits shutdown error responses.
     * @param  bool                     $displayErrorDetails Whether error details should be included in responses.
     * @param  callable|null            $lastErrorResolver   An optional callable that returns the last PHP error payload.
     * @return void                     Returns after assigning immutable dependencies.
     */
    public function __construct(
        ServerRequestInterface $request,
        ErrorResponderInterface $errorResponder,
        ResponseEmitterInterface $responseEmitter,
        bool $displayErrorDetails,
        ?callable $lastErrorResolver = null
    ) {
        $this->request = $request;
        $this->errorResponder = $errorResponder;
        $this->responseEmitter = $responseEmitter;
        $this->displayErrorDetails = $displayErrorDetails;
        $this->lastErrorResolver = $lastErrorResolver !== null
            ? Closure::fromCallable($lastErrorResolver)
            : null;
        $this->initialOutputBufferLevel = ob_get_level();
    }

    /**
     * Processes shutdown state and emits a fatal error response when needed.
     *
     * @return void      Returns after processing shutdown error handling.
     * @throws Throwable When response creation or response emission fails.
     */
    public function __invoke(): void
    {
        $error = $this->getLastError();
        if ($error === null || !$this->isFatalError($error)) {
            return;
        }

        $exception = new HttpInternalServerErrorException(
            $this->request,
            $this->getErrorMessage($error)
        );

        $response = $this->errorResponder->createResponse(
            $this->request,
            $exception,
            $this->displayErrorDetails
        );

        $this->clearOutputBuffers();

        $this->responseEmitter->emit($response);
    }

    /**
     * Returns the last PHP runtime error from the configured source.
     *
     * @return array{type?:int,message?:string,file?:string,line?:int}|null The normalized last error payload, or null when unavailable.
     * @internal This helper is only used by shutdown processing internals.
     */
    private function getLastError(): ?array
    {
        $error = $this->lastErrorResolver !== null
            ? ($this->lastErrorResolver)()
            : error_get_last();

        return is_array($error) ? $error : null;
    }

    /**
     * Determines whether a normalized error payload represents a fatal shutdown condition.
     *
     * @param  array{type?:int} $error The normalized error payload to evaluate.
     * @return bool             Returns true when the error type is fatal.
     * @internal This helper is only used by shutdown processing internals.
     */
    private function isFatalError(array $error): bool
    {
        $type = $error['type'] ?? null;

        return is_int($type) && in_array($type, self::FATAL_ERROR_TYPES, true);
    }

    /**
     * Returns the error message for the configured detail level.
     *
     * @param  array{type?:int,message?:string,file?:string,line?:int} $error The normalized error payload used to derive message details.
     * @return string                                                  The generated error message.
     * @internal This helper is only used by shutdown processing internals.
     */
    private function getErrorMessage(array $error): string
    {
        if (!$this->displayErrorDetails) {
            return 'An error while processing your request. Please try again later.';
        }

        $errorFile = (string)($error['file'] ?? 'unknown');
        $errorLine = (int)($error['line'] ?? 0);
        $errorMessage = (string)($error['message'] ?? 'Unknown error');

        return "FATAL ERROR: {$errorMessage} on line {$errorLine} in file {$errorFile}.";
    }

    /**
     * Clears output buffers created after handler initialisation.
     *
     * @return void Returns after attempting to clear and close active buffers.
     * @internal This helper is only used by shutdown processing internals.
     */
    private function clearOutputBuffers(): void
    {
        while (ob_get_level() > $this->initialOutputBufferLevel) {
            if (!ob_end_clean()) {
                ob_clean();
                break;
            }
        }
    }
}
