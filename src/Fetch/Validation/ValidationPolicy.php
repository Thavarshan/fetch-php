<?php

declare(strict_types=1);

namespace Fetch\Validation;

/**
 * Immutable request and response validation configuration.
 *
 * A schema enables validation for its direction; OpenAPI checks run only when
 * both a specification path and the contract-validation flag are set.
 *
 * @psalm-immutable
 */
final readonly class ValidationPolicy
{
    /**
     * Define optional schemas and OpenAPI contract validation behavior.
     *
     * @param  mixed  $requestSchema  Schema applied to outgoing request data
     * @param  mixed  $responseSchema  Schema applied to response data
     * @param  string|null  $openApiSpec  Local JSON or YAML specification path
     * @param  bool  $validateContracts  Whether to check resolved OpenAPI
     *                                   contracts
     */
    public function __construct(
        public mixed $requestSchema = null,
        public mixed $responseSchema = null,
        public ?string $openApiSpec = null,
        public bool $validateContracts = false,
    ) {}

    /**
     * Create a copy with recognized validation options overridden.
     *
     * @param  array<string, mixed>  $options
     */
    public function withOptions(array $options): self
    {
        return new self(
            requestSchema: $options['request_schema'] ?? $this->requestSchema,
            responseSchema: $options['response_schema'] ??
                $this->responseSchema,
            openApiSpec: $options['openapi_spec'] ?? $this->openApiSpec,
            validateContracts: (bool) ($options['validate_contracts'] ??
                $this->validateContracts),
        );
    }

    /**
     * Report whether any configured validation path is active.
     */
    public function isEnabled(): bool
    {
        return $this->requestSchema !== null ||
            $this->responseSchema !== null ||
            ($this->validateContracts && $this->openApiSpec !== null);
    }
}
