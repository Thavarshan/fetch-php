<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Cache\CacheManager;
use Fetch\Exceptions\AsyncTransportException;
use Fetch\Interfaces\AsyncTransport;
use Fetch\Interfaces\Response as FetchResponseInterface;
use Fetch\Support\RequestContext;
use Fetch\Support\RetryStrategy;
use Fetch\Validation\ValidationConfigurationException;
use Fetch\Validation\ValidationDependencyException;
use Fetch\Validation\ValidationException;
use Fetch\Validation\ValidationManager;
use Fetch\Validation\ValidationPolicy;
use GuzzleHttp\ClientInterface;
use Matrix\Exceptions\AsyncException;
use Matrix\Exceptions\CancellationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;

use function Matrix\Support\reject;
use function Matrix\Support\resolve;

/**
 * Coordinates middleware, validation, cache/mock selection, execution, and
 *     lifecycle events.
 */
final class RequestCoordinator
{
    /** @param array<int, mixed> $middleware */
    public function __construct(
        private readonly RequestBuilder $requestBuilder,
        private readonly ValidationManager $validationManager,
        private readonly ValidationPolicy $validationPolicy,
        private readonly ClientInterface $httpClient,
        private readonly AsyncTransport $asyncTransport,
        private readonly RetryStrategy $retryStrategy,
        private readonly ?CacheManager $cacheManager,
        private readonly array $middleware,
        private readonly RequestCoordinatorHooks $hooks,
    ) {}

    /**
     * @param  array<string, mixed>  $guzzleOptions
     * @return FetchResponseInterface|PromiseInterface<FetchResponseInterface>
     */
    public function send(
        string $method,
        string $uri,
        array $guzzleOptions,
        RequestContext $context,
    ): FetchResponseInterface|PromiseInterface {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $requestId = ($this->hooks->startProfiling)($method, $uri);
        $guzzleOptions = $this->requestBuilder->normalizeStreamBody(
            $guzzleOptions,
        );
        $captureStreamBody = $this->capturesRequestBody();
        [
            $psrRequest,
            $seedBody,
        ] = $this->requestBuilder->buildMiddlewareRequest(
            $method,
            $uri,
            $guzzleOptions,
            $captureStreamBody,
        );
        $guzzleOptions = $this->requestBuilder->replaceConsumedNonSeekableBody(
            $guzzleOptions,
            $seedBody,
        );
        $initialBody = $psrRequest->getBody();
        $coreDispatched = false;
        $lastMiddlewareRequest = $psrRequest;

        $core = function (RequestInterface $request) use (
            $guzzleOptions,
            $seedBody,
            $initialBody,
            $context,
            $startTime,
            $startMemory,
            $requestId,
            &$coreDispatched,
        ): FetchResponseInterface|PromiseInterface {
            $coreDispatched = true;
            $mergedOptions = $this->requestBuilder->mergeMiddlewareRequest(
                $request,
                $guzzleOptions,
                $seedBody,
                $initialBody,
            );

            try {
                if ($this->capturesRequestBody()) {
                    [
                        $request,
                        $mergedOptions,
                    ] = $this->requestBuilder->makeRequestBodyReplayable(
                        $request,
                        $mergedOptions,
                    );
                }

                $request = $this->requestBuilder->prepareValidationRequest(
                    $request,
                    $mergedOptions,
                );
                $mergedOptions['headers'] = $request->getHeaders();
                unset($mergedOptions['query']);
                $operation = $this->validationManager->validateRequest(
                    $request,
                    $mergedOptions,
                    $this->validationPolicy,
                );
            } catch (Throwable $exception) {
                if ($context->isAsync()) {
                    return reject($exception);
                }

                throw $exception;
            }

            return $this->executeCore(
                $request->getMethod(),
                (string) $request->getUri(),
                $mergedOptions,
                $context,
                $startTime,
                $startMemory,
                $requestId,
                $operation,
            );
        };

        $result =
            $this->middleware === []
                ? $core($psrRequest)
                : $this->coerceMiddlewareResult(
                    (new MiddlewarePipeline($this->middleware))->handle(
                        $psrRequest,
                        $core,
                        static function (RequestInterface $request) use (
                            &$lastMiddlewareRequest,
                        ): void {
                            $lastMiddlewareRequest = $request;
                        },
                    ),
                );

        if ($result instanceof PromiseInterface) {
            return $result->then(
                function (mixed $response) use (
                    &$coreDispatched,
                    &$lastMiddlewareRequest,
                    $guzzleOptions,
                    $seedBody,
                    $initialBody,
                    $context,
                    $startTime,
                    $startMemory,
                    $requestId,
                ): FetchResponseInterface|PromiseInterface {
                    if ($coreDispatched) {
                        return $this->requireFetchResponse($response);
                    }

                    return $this->completeMiddlewareShortCircuit(
                        $response,
                        $lastMiddlewareRequest,
                        $guzzleOptions,
                        $seedBody,
                        $initialBody,
                        $context,
                        $startTime,
                        $startMemory,
                        $requestId,
                    );
                },
                function (Throwable $exception) use (
                    &$coreDispatched,
                    &$lastMiddlewareRequest,
                    $guzzleOptions,
                    $seedBody,
                    $initialBody,
                    $context,
                    $startTime,
                    $requestId,
                ): never {
                    if ($coreDispatched) {
                        throw $exception;
                    }

                    $this->failMiddlewareBeforeDispatch(
                        $exception,
                        $lastMiddlewareRequest,
                        $guzzleOptions,
                        $seedBody,
                        $initialBody,
                        $context,
                        $startTime,
                        $requestId,
                    );
                },
            );
        }

        if ($coreDispatched) {
            return $this->requireFetchResponse($result);
        }

        return $this->completeMiddlewareShortCircuit(
            $result,
            $lastMiddlewareRequest,
            $guzzleOptions,
            $seedBody,
            $initialBody,
            $context,
            $startTime,
            $startMemory,
            $requestId,
        );
    }

    /**
     * Complete a middleware response after the pipeline settles without
     * dispatching its core.
     *
     * @param  array<string, mixed>  $guzzleOptions
     * @return FetchResponseInterface|PromiseInterface<FetchResponseInterface>
     */
    private function completeMiddlewareShortCircuit(
        mixed $result,
        RequestInterface $request,
        array $guzzleOptions,
        ?string $seedBody,
        ?StreamInterface $initialBody,
        RequestContext $context,
        float $startTime,
        int $startMemory,
        ?string $requestId,
    ): FetchResponseInterface|PromiseInterface {
        try {
            $shortCircuitOptions =
                $this->requestBuilder->mergeMiddlewareRequest(
                    $request,
                    $guzzleOptions,
                    $seedBody,
                    $initialBody,
                );
            if ($this->capturesRequestBody()) {
                [
                    $request,
                    $shortCircuitOptions,
                ] = $this->requestBuilder->makeRequestBodyReplayable(
                    $request,
                    $shortCircuitOptions,
                );
            }

            $request = $this->requestBuilder->prepareValidationRequest(
                $request,
                $shortCircuitOptions,
            );
            unset($shortCircuitOptions['query']);
            $operation = $this->validationManager->validateRequest(
                $request,
                $shortCircuitOptions,
                $this->validationPolicy,
            );
        } catch (Throwable $exception) {
            if ($context->isAsync()) {
                return reject($exception);
            }

            throw $exception;
        }

        $method = $request->getMethod();
        $uri = (string) $request->getUri();
        $eventRequest = ($this->hooks->eventRequest)(
            $method,
            $uri,
            $shortCircuitOptions['headers'] ?? [],
        );
        ($this->hooks->emitRequestEvent)($eventRequest, $context, $startTime);

        $complete = function (mixed $response) use (
            $method,
            $uri,
            $shortCircuitOptions,
            $context,
            $operation,
            $eventRequest,
            $startTime,
            $startMemory,
            $requestId,
        ): FetchResponseInterface {
            $finalResponse = $this->requireFetchResponse($response);
            $this->validationManager->validateResponse(
                $finalResponse,
                $this->validationPolicy,
                $operation,
            );
            ($this->hooks->recordProfilingEvent)($requestId, 'response_start');
            ($this->hooks->endProfiling)(
                $requestId,
                $finalResponse->getStatusCode(),
            );
            $this->attachDebugInfo(
                $method,
                $uri,
                $shortCircuitOptions,
                $finalResponse,
                $startTime,
                $startMemory,
            );
            ($this->hooks->emitResponseEvent)(
                $eventRequest,
                $finalResponse,
                $context,
                $startTime,
            );

            return $finalResponse;
        };

        $fail = function (Throwable $exception) use (
            $method,
            $uri,
            $context,
            $eventRequest,
            $startTime,
            $requestId,
        ): never {
            ($this->hooks->endProfiling)($requestId, null);
            ($this->hooks->emitErrorEvent)(
                $eventRequest,
                $exception,
                $context,
                $startTime,
            );

            throw ($this->hooks->withErrorContext)($exception, $method, $uri);
        };

        if ($result instanceof PromiseInterface) {
            return $result->then($complete)->catch($fail);
        }

        if ($context->isAsync()) {
            return resolve($result)->then($complete)->catch($fail);
        }

        try {
            return $complete($result);
        } catch (Throwable $exception) {
            return $fail($exception);
        }
    }

    /**
     * Propagate a middleware failure that occurs before the core request is
     * dispatched.
     *
     * @param  array<string, mixed>  $guzzleOptions
     */
    private function failMiddlewareBeforeDispatch(
        Throwable $exception,
        RequestInterface $request,
        array $guzzleOptions,
        ?string $seedBody,
        ?StreamInterface $initialBody,
        RequestContext $context,
        float $startTime,
        ?string $requestId,
    ): never {
        $shortCircuitOptions = $this->requestBuilder->mergeMiddlewareRequest(
            $request,
            $guzzleOptions,
            $seedBody,
            $initialBody,
        );
        if ($this->capturesRequestBody()) {
            [
                $request,
                $shortCircuitOptions,
            ] = $this->requestBuilder->makeRequestBodyReplayable(
                $request,
                $shortCircuitOptions,
            );
        }

        $request = $this->requestBuilder->prepareValidationRequest(
            $request,
            $shortCircuitOptions,
        );
        unset($shortCircuitOptions['query']);
        $this->validationManager->validateRequest(
            $request,
            $shortCircuitOptions,
            $this->validationPolicy,
        );

        $method = $request->getMethod();
        $uri = (string) $request->getUri();
        $eventRequest = ($this->hooks->eventRequest)(
            $method,
            $uri,
            $shortCircuitOptions['headers'] ?? [],
        );
        ($this->hooks->emitRequestEvent)($eventRequest, $context, $startTime);
        ($this->hooks->endProfiling)($requestId, null);
        ($this->hooks->emitErrorEvent)(
            $eventRequest,
            $exception,
            $context,
            $startTime,
        );

        throw ($this->hooks->withErrorContext)($exception, $method, $uri);
    }

    /**
     * @param  array<string, mixed>  $guzzleOptions
     * @return FetchResponseInterface|PromiseInterface<FetchResponseInterface>
     */
    private function executeCore(
        string $method,
        string $uri,
        array $guzzleOptions,
        RequestContext $context,
        float $startTime,
        int $startMemory,
        ?string $requestId,
        ?object $operation,
    ): FetchResponseInterface|PromiseInterface {
        $eventRequest = ($this->hooks->eventRequest)(
            $method,
            $uri,
            $guzzleOptions['headers'] ?? [],
        );
        ($this->hooks->emitRequestEvent)($eventRequest, $context, $startTime);

        if (($this->hooks->hasRedirectListeners)()) {
            $guzzleOptions = ($this->hooks->attachRedirectListener)(
                $guzzleOptions,
                $eventRequest,
                $context,
            );
        }

        $mockResponse = ($this->hooks->mockRequest)(
            $method,
            $uri,
            $guzzleOptions,
        );
        if ($mockResponse !== null) {
            return $this->completeImmediateResponse(
                $mockResponse,
                $method,
                $uri,
                $guzzleOptions,
                $context,
                $operation,
                $eventRequest,
                $startTime,
                $startMemory,
                $requestId,
            );
        }

        $cachedResult = null;
        $requestOptionsWithAsync = array_replace(
            $context->toArray(),
            $guzzleOptions,
            ['async' => $context->isAsync()],
        );
        unset($requestOptionsWithAsync['query']);
        if ($this->cacheManager !== null) {
            $cachedResult = $this->cacheManager->getCachedResponse(
                $method,
                $uri,
                $requestOptionsWithAsync,
            );
            if ($cachedResult['response'] !== null) {
                return $this->completeImmediateResponse(
                    $cachedResult['response'],
                    $method,
                    $uri,
                    $guzzleOptions,
                    $context,
                    $operation,
                    $eventRequest,
                    $startTime,
                    $startMemory,
                    $requestId,
                );
            }

            if ($cachedResult['cached'] !== null) {
                $guzzleOptions = $this->cacheManager->addConditionalHeaders(
                    $guzzleOptions,
                    $cachedResult['cached'],
                );
            }
        }

        ($this->hooks->logRequest)($method, $uri, $guzzleOptions);

        if ($context->isAsync()) {
            return $this->executeAsync(
                $method,
                $uri,
                $guzzleOptions,
                $startTime,
                $startMemory,
                $requestId,
                $context,
            )
                ->then(function (FetchResponseInterface $response) use (
                    $eventRequest,
                    $context,
                    $startTime,
                    $operation,
                ): FetchResponseInterface {
                    $this->validationManager->validateResponse(
                        $response,
                        $this->validationPolicy,
                        $operation,
                    );
                    ($this->hooks->emitResponseEvent)(
                        $eventRequest,
                        $response,
                        $context,
                        $startTime,
                    );

                    return $response;
                })
                ->catch(function (Throwable $exception) use (
                    $method,
                    $uri,
                    $eventRequest,
                    $context,
                    $startTime,
                ): never {
                    ($this->hooks->emitErrorEvent)(
                        $eventRequest,
                        $exception,
                        $context,
                        $startTime,
                    );

                    throw ($this->hooks->withErrorContext)(
                        $exception,
                        $method,
                        $uri,
                    );
                });
        }

        try {
            $response = $this->executeSyncWithCache(
                $method,
                $uri,
                $guzzleOptions,
                $requestOptionsWithAsync,
                $startTime,
                $startMemory,
                $cachedResult,
                $requestId,
                $context,
                $operation,
            );
            ($this->hooks->emitResponseEvent)(
                $eventRequest,
                $response,
                $context,
                $startTime,
            );

            return $response;
        } catch (Throwable $exception) {
            ($this->hooks->emitErrorEvent)(
                $eventRequest,
                $exception,
                $context,
                $startTime,
            );

            throw ($this->hooks->withErrorContext)($exception, $method, $uri);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $requestOptions
     * @param  array<string, mixed>|null  $cachedResult
     */
    private function executeSyncWithCache(
        string $method,
        string $uri,
        array $options,
        array $requestOptions,
        float $startTime,
        int $startMemory,
        ?array $cachedResult,
        ?string $requestId,
        RequestContext $context,
        ?object $operation,
    ): FetchResponseInterface {
        try {
            $response = $this->executeSync(
                $method,
                $uri,
                $options,
                $startTime,
                $startMemory,
                $requestId,
                $context,
            );
            if (
                $response->getStatusCode() === 304 &&
                $cachedResult !== null &&
                isset($cachedResult['cached']) &&
                $this->cacheManager !== null
            ) {
                $response = $this->cacheManager->handleNotModified(
                    $cachedResult['cached'],
                    $response,
                );
            }

            $this->validationManager->validateResponse(
                $response,
                $this->validationPolicy,
                $operation,
            );
            $this->cacheManager?->cacheResponse(
                $method,
                $uri,
                $response,
                $requestOptions,
            );

            return $response;
        } catch (Throwable $exception) {
            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            if (
                $cachedResult !== null &&
                isset($cachedResult['cached']) &&
                $this->cacheManager !== null
            ) {
                $staleResponse = $this->cacheManager->handleStaleIfError(
                    $cachedResult['cached'],
                    $method,
                    $uri,
                );
                if ($staleResponse !== null) {
                    $this->validationManager->validateResponse(
                        $staleResponse,
                        $this->validationPolicy,
                        $operation,
                    );
                    ($this->hooks->recordProfilingEvent)(
                        $requestId,
                        'response_start',
                    );
                    ($this->hooks->endProfiling)(
                        $requestId,
                        $staleResponse->getStatusCode(),
                    );
                    $this->attachDebugInfo(
                        $method,
                        $uri,
                        $options,
                        $staleResponse,
                        $startTime,
                        $startMemory,
                    );

                    return $staleResponse;
                }
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $options */
    private function executeSync(
        string $method,
        string $uri,
        array $options,
        float $startTime,
        int $startMemory,
        ?string $requestId,
        ?RequestContext $context,
    ): FetchResponseInterface {
        $requestId ??= ($this->hooks->startProfiling)($method, $uri);

        try {
            $response = (new RequestExecutor($this->httpClient))->execute(
                $method,
                $uri,
                $options,
                $this->retryStrategy,
                function (
                    int $attempt,
                    int $maxAttempts,
                    Throwable $exception,
                    int $delay,
                ) use ($context): void {
                    ($this->hooks->notifyRetry)(
                        $attempt,
                        $maxAttempts,
                        $exception,
                        $delay,
                        $context,
                    );
                },
                function () use ($requestId): void {
                    ($this->hooks->recordProfilingEvent)(
                        $requestId,
                        'request_sent',
                    );
                },
            );
            $duration = microtime(true) - $startTime;
            ($this->hooks->recordProfilingEvent)($requestId, 'response_start');
            ($this->hooks->endProfiling)(
                $requestId,
                $response->getStatusCode(),
            );
            $this->attachDebugInfo(
                $method,
                $uri,
                $options,
                $response,
                $startTime,
                $startMemory,
            );
            if ($response instanceof Response) {
                ($this->hooks->logResponse)($response, $duration);
            }

            return $response;
        } catch (Throwable $exception) {
            ($this->hooks->endProfiling)($requestId, null);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return PromiseInterface<FetchResponseInterface>
     */
    private function executeAsync(
        string $method,
        string $uri,
        array $options,
        float $startTime,
        int $startMemory,
        ?string $requestId,
        ?RequestContext $context,
    ): PromiseInterface {
        $requestId ??= ($this->hooks->startProfiling)($method, $uri);

        return (new AsyncRequestExecutor($this->asyncTransport))
            ->execute(
                $method,
                $uri,
                $options,
                $this->retryStrategy,
                function (
                    int $attempt,
                    int $maxAttempts,
                    Throwable $exception,
                    int $delay,
                ) use ($context): void {
                    ($this->hooks->notifyRetry)(
                        $attempt,
                        $maxAttempts,
                        $exception,
                        $delay,
                        $context,
                    );
                },
                function () use ($requestId): void {
                    ($this->hooks->recordProfilingEvent)(
                        $requestId,
                        'request_sent',
                    );
                },
            )
            ->then(function (FetchResponseInterface $response) use (
                $method,
                $uri,
                $options,
                $startTime,
                $startMemory,
                $requestId,
            ): FetchResponseInterface {
                $duration = microtime(true) - $startTime;
                ($this->hooks->recordProfilingEvent)(
                    $requestId,
                    'response_start',
                );
                ($this->hooks->endProfiling)(
                    $requestId,
                    $response->getStatusCode(),
                );
                $this->attachDebugInfo(
                    $method,
                    $uri,
                    $options,
                    $response,
                    $startTime,
                    $startMemory,
                );
                if ($response instanceof Response) {
                    ($this->hooks->logResponse)($response, $duration);
                }

                return $response;
            })
            ->catch(function (Throwable $exception) use (
                $method,
                $uri,
                $requestId,
            ): never {
                ($this->hooks->endProfiling)($requestId, null);
                if (
                    $exception instanceof AsyncTransportException ||
                    $exception instanceof CancellationException ||
                    $exception instanceof ValidationException ||
                    $exception instanceof ValidationDependencyException ||
                    $exception instanceof ValidationConfigurationException
                ) {
                    throw $exception;
                }

                ($this->hooks->logAsyncFailure)($method, $uri, $exception);
                $wrapped = ($this->hooks->withErrorContext)(
                    $exception,
                    $method,
                    $uri,
                );

                throw new AsyncException(
                    $wrapped->getMessage(),
                    (int) $wrapped->getCode(),
                    $wrapped,
                );
            });
    }

    /** @param array<string, mixed> $options */
    private function attachDebugInfo(
        string $method,
        string $uri,
        array $options,
        ResponseInterface $response,
        float $startTime,
        int $startMemory,
    ): void {
        $debugInfo = ($this->hooks->captureDebugSnapshot)(
            $method,
            $uri,
            $options,
            $response,
            $startTime,
            $startMemory,
            ($this->hooks->connectionStats)(),
        );

        if ($debugInfo !== null && $response instanceof Response) {
            $response->withDebugInfo($debugInfo);
        }
    }

    /**
     * Complete mock and cache responses through the same validation and event
     * lifecycle.
     *
     * @param  array<string, mixed>  $options
     * @return FetchResponseInterface|PromiseInterface<FetchResponseInterface>
     */
    private function completeImmediateResponse(
        FetchResponseInterface $response,
        string $method,
        string $uri,
        array $options,
        RequestContext $context,
        ?object $operation,
        RequestInterface $eventRequest,
        float $startTime,
        int $startMemory,
        ?string $requestId,
    ): FetchResponseInterface|PromiseInterface {
        $complete = function (mixed $value) use (
            $response,
            $method,
            $uri,
            $options,
            $context,
            $operation,
            $eventRequest,
            $startTime,
            $startMemory,
            $requestId,
        ): FetchResponseInterface {
            $finalResponse =
                $value instanceof ResponseInterface ? $value : $response;
            $this->validationManager->validateResponse(
                $finalResponse,
                $this->validationPolicy,
                $operation,
            );
            ($this->hooks->recordProfilingEvent)($requestId, 'response_start');
            ($this->hooks->endProfiling)(
                $requestId,
                $finalResponse->getStatusCode(),
            );
            $this->attachDebugInfo(
                $method,
                $uri,
                $options,
                $finalResponse,
                $startTime,
                $startMemory,
            );
            ($this->hooks->emitResponseEvent)(
                $eventRequest,
                $finalResponse,
                $context,
                $startTime,
            );

            return $this->requireFetchResponse($finalResponse);
        };

        $fail = function (Throwable $exception) use (
            $method,
            $uri,
            $context,
            $eventRequest,
            $startTime,
            $requestId,
        ): never {
            ($this->hooks->endProfiling)($requestId, null);
            ($this->hooks->emitErrorEvent)(
                $eventRequest,
                $exception,
                $context,
                $startTime,
            );

            throw ($this->hooks->withErrorContext)($exception, $method, $uri);
        };

        if ($context->isAsync()) {
            return resolve($response)->then($complete)->catch($fail);
        }

        try {
            return $complete($response);
        } catch (Throwable $exception) {
            return $fail($exception);
        }
    }

    private function coerceMiddlewareResult(
        ResponseInterface|PromiseInterface $result,
    ): ResponseInterface|PromiseInterface {
        if ($result instanceof PromiseInterface) {
            return $result->then(
                fn (mixed $response): mixed => $this->coerceResponseValue(
                    $response,
                ),
            );
        }

        return $this->coerceResponseValue($result);
    }

    private function capturesRequestBody(): bool
    {
        return $this->validationPolicy->requestSchema !== null ||
            ($this->validationPolicy->validateContracts &&
                $this->validationPolicy->openApiSpec !== null);
    }

    private function coerceResponseValue(mixed $response): mixed
    {
        if ($response instanceof FetchResponseInterface) {
            return $response;
        }

        if ($response instanceof ResponseInterface) {
            return Response::createFromBase($response);
        }

        return $response;
    }

    private function requireFetchResponse(
        mixed $response,
    ): FetchResponseInterface {
        if ($response instanceof FetchResponseInterface) {
            return $response;
        }

        if ($response instanceof ResponseInterface) {
            return Response::createFromBase($response);
        }

        throw new RuntimeException(
            'The request pipeline did not return an HTTP response.',
        );
    }
}
