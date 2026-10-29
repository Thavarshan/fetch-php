<?php

declare(strict_types=1);

namespace Fetch\Validation;

use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ValidationManager
{
    private ?ValidatorInterface $schemaValidator;

    private OpenApiContractValidator $openApiValidator;

    public function __construct(
        ?ValidatorInterface $schemaValidator = null,
        ?OpenApiContractValidator $openApiValidator = null,
    ) {
        $this->schemaValidator = $schemaValidator;
        $this->openApiValidator = $openApiValidator ?? new OpenApiContractValidator;
    }

    /**
     * Validate the normalized request payload and its OpenAPI operation.
     *
     * @param  array<string, mixed>  $options
     */
    public function validateRequest(
        RequestInterface $request,
        array $options,
        ValidationPolicy $policy,
    ): ?object {
        if ($policy->requestSchema !== null) {
            $result = $this->schemaValidator()->validate($this->requestData($options, $request), $policy->requestSchema);
            if (! $result->isValid()) {
                throw new RequestValidationException(
                    sprintf('The outgoing request failed schema validation with %d error(s).', $result->count()),
                    $result,
                );
            }
        }

        if ($policy->validateContracts && $policy->openApiSpec !== null) {
            try {
                return $this->openApiValidator->validateRequest($request, $policy->openApiSpec);
            } catch (ValidationException $exception) {
                throw new RequestValidationException($exception->getMessage(), $exception->result(), $exception);
            }
        }

        return null;
    }

    public function validateResponse(
        ResponseInterface $response,
        ValidationPolicy $policy,
        ?object $operation = null,
    ): void {
        if ($policy->responseSchema !== null) {
            $result = $this->schemaValidator()->validate($this->responseData($response), $policy->responseSchema);
            if (! $result->isValid()) {
                throw new ResponseValidationException(
                    sprintf('The response failed schema validation with %d error(s).', $result->count()),
                    $result,
                );
            }
        }

        if ($policy->validateContracts && $policy->openApiSpec !== null && $operation !== null) {
            if (! $operation instanceof OperationAddress) {
                throw new ValidationConfigurationException('The OpenAPI operation could not be resolved for this response.');
            }

            try {
                $this->openApiValidator->validateResponse($operation, $response, $policy->openApiSpec);
            } catch (ValidationException $exception) {
                throw new ResponseValidationException($exception->getMessage(), $exception->result(), $exception);
            }
        }
    }

    /** @param array<string, mixed> $options */
    private function requestData(array $options, RequestInterface $request): mixed
    {
        if (array_key_exists('json', $options)) {
            return $options['json'];
        }

        if (array_key_exists('form_params', $options)) {
            return $options['form_params'];
        }

        if (str_contains(strtolower($request->getHeaderLine('Content-Type')), 'json')) {
            return $this->decodeJson((string) $request->getBody(), 'request');
        }

        return null;
    }

    private function responseData(ResponseInterface $response): mixed
    {
        if (! str_contains(strtolower($response->getHeaderLine('Content-Type')), 'json')) {
            return null;
        }

        $body = $response->getBody();
        $position = $body->tell();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $contents = $body->getContents();
        if ($body->isSeekable()) {
            $body->seek($position);
        }

        return $this->decodeJson($contents, 'response');
    }

    private function decodeJson(string $json, string $direction): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $result = new ValidationResult([new ValidationError([], 'The JSON payload is malformed.', ['keyword' => 'json'])]);
            $exceptionClass = $direction === 'request' ? RequestValidationException::class : ResponseValidationException::class;

            throw new $exceptionClass(
                sprintf('The %s payload is not valid JSON.', $direction),
                $result,
                $exception,
            );
        }
    }

    private function schemaValidator(): ValidatorInterface
    {
        return $this->schemaValidator ??= new JsonSchemaValidator;
    }
}
