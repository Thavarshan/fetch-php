<?php

declare(strict_types=1);

namespace Fetch\Validation;

use League\OpenAPIValidation\PSR7\Exception\Validation\AddressValidationFailed;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidCookies;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidHeaders;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidParameter;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidQueryArgs;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;
use League\OpenAPIValidation\PSR7\Exception\Validation\RequiredParameterMissing;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\RequestValidator as LeagueRequestValidator;
use League\OpenAPIValidation\PSR7\ResponseValidator as LeagueResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class OpenApiContractValidator
{
    private ?LeagueRequestValidator $requestValidator = null;

    private ?LeagueResponseValidator $responseValidator = null;

    private ?string $loadedSpec = null;

    public function validateRequest(RequestInterface $request, string $specPath): OperationAddress
    {
        $this->load($specPath);

        try {
            if ($this->requestValidator === null) {
                throw new ValidationConfigurationException('The OpenAPI request validator was not initialized.');
            }

            return $this->requestValidator->validate($request);
        } catch (Throwable $exception) {
            throw new ValidationException(
                'The request does not satisfy the configured OpenAPI 3.0 contract.',
                new ValidationResult([$this->toValidationError($exception, 'request')]),
                $exception,
            );
        }
    }

    public function validateResponse(OperationAddress $operation, ResponseInterface $response, string $specPath): void
    {
        $this->load($specPath);

        try {
            if ($this->responseValidator === null) {
                throw new ValidationConfigurationException('The OpenAPI response validator was not initialized.');
            }

            $this->responseValidator->validate($operation, $response);
        } catch (Throwable $exception) {
            throw new ValidationException(
                'The response does not satisfy the configured OpenAPI 3.0 contract.',
                new ValidationResult([$this->toValidationError($exception, 'response', $response->getStatusCode())]),
                $exception,
            );
        }
    }

    private function load(string $specPath): void
    {
        if ($this->loadedSpec === $specPath) {
            return;
        }

        if (! class_exists(ValidatorBuilder::class)) {
            throw new ValidationDependencyException(
                'OpenAPI contract validation requires league/openapi-psr7-validator:^0.24. Install it to use OpenApiContractValidator.'
            );
        }

        if (! is_file($specPath) || ! is_readable($specPath)) {
            throw new ValidationConfigurationException('The OpenAPI specification must be a readable local file.');
        }

        $contents = file_get_contents($specPath);
        if (! is_string($contents)) {
            throw new ValidationConfigurationException('The OpenAPI specification could not be read.');
        }

        $document = $this->parseDocument($contents, $specPath);
        if (! is_array($document) || ! isset($document['openapi']) || ! is_string($document['openapi'])) {
            throw new ValidationConfigurationException('The OpenAPI document must declare an openapi version.');
        }

        if (preg_match('/^3\.0\.\d+$/', $document['openapi']) !== 1) {
            throw new ValidationConfigurationException('Only OpenAPI 3.0.x specifications are supported.');
        }

        $this->assertInternalReferencesOnly($document);

        try {
            $json = json_encode($document, JSON_THROW_ON_ERROR);
            $builder = (new ValidatorBuilder)->fromJson($json);
            $requestValidator = $builder->getRequestValidator();
            $responseValidator = $builder->getResponseValidator();
        } catch (Throwable $exception) {
            throw new ValidationConfigurationException('The OpenAPI 3.0 specification is invalid.', previous: $exception);
        }

        $this->requestValidator = $requestValidator;
        $this->responseValidator = $responseValidator;
        $this->loadedSpec = $specPath;
    }

    /** @return array<string, mixed> */
    private function parseDocument(string $contents, string $path): array
    {
        try {
            $document = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($document)) {
                return $document;
            }
        } catch (\JsonException) {
            // Try YAML below.
        }

        try {
            if (class_exists(Yaml::class)) {
                /** @var mixed $document */
                $document = Yaml::parse($contents);
            } elseif (function_exists('yaml_parse')) {
                /** @var mixed $document */
                $document = yaml_parse($contents);
            } else {
                throw new ValidationDependencyException(
                    'YAML OpenAPI files require symfony/yaml or ext-yaml; install symfony/yaml to enable YAML support.'
                );
            }
        } catch (ValidationDependencyException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ValidationConfigurationException('The OpenAPI specification is not valid JSON or YAML.', previous: $exception);
        }

        if (! is_array($document)) {
            throw new ValidationConfigurationException('The OpenAPI specification must contain an object document.');
        }

        return $document;
    }

    /** @param array<mixed> $value */
    private function assertInternalReferencesOnly(array $value): void
    {
        foreach ($value as $key => $item) {
            if ($key === '$ref' && (! is_string($item) || ! str_starts_with($item, '#'))) {
                throw new ValidationConfigurationException(
                    'External OpenAPI references are disabled; use references within the specification document.'
                );
            }

            if (is_array($item)) {
                $this->assertInternalReferencesOnly($item);
            }
        }
    }

    private function toValidationError(Throwable $exception, string $direction, ?int $statusCode = null): ValidationError
    {
        $path = [$direction];
        $operation = null;

        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause instanceof InvalidBody) {
                $path[0] = 'body';
            } elseif ($cause instanceof InvalidHeaders) {
                $path[0] = 'headers';
            } elseif ($cause instanceof InvalidCookies) {
                $path[0] = 'cookies';
            } elseif ($cause instanceof InvalidPath) {
                $path[0] = 'path';
            } elseif ($cause instanceof InvalidQueryArgs) {
                $path[0] = 'query';
            } elseif ($cause instanceof InvalidSecurity) {
                $path[0] = 'security';
            } elseif ($cause instanceof InvalidParameter) {
                $path[0] = 'parameters';
                $path[] = $cause->name();
            } elseif ($cause instanceof RequiredParameterMissing) {
                $path[0] = 'parameters';
                $path[] = $cause->name();
            }

            if ($cause instanceof AddressValidationFailed) {
                $operation = $cause->getAddress();
            }

            if ($cause instanceof SchemaMismatch && $cause->dataBreadCrumb() !== null) {
                foreach ($cause->dataBreadCrumb()->buildChain() as $segment) {
                    if ($segment !== null) {
                        $path[] = $segment;
                    }
                }
            }
        }

        $constraint = [
            'keyword' => 'openapi',
            'exception' => $exception::class,
        ];
        if ($operation !== null) {
            $constraint['operation_method'] = $operation->method();
            $constraint['operation_path'] = $operation->path();
        }
        if ($statusCode !== null) {
            $constraint['status_code'] = $statusCode;
        }

        return new ValidationError($path, sprintf('The OpenAPI %s contract validation failed.', $direction), $constraint);
    }
}
