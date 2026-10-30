<?php

declare(strict_types=1);

namespace Fetch\Validation;

/** @psalm-immutable */
final readonly class ValidationPolicy
{
    public function __construct(
        public mixed $requestSchema = null,
        public mixed $responseSchema = null,
        public ?string $openApiSpec = null,
        public bool $validateContracts = false,
    ) {}

    /** @param array<string, mixed> $options */
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

    public function isEnabled(): bool
    {
        return $this->requestSchema !== null ||
            $this->responseSchema !== null ||
            ($this->validateContracts && $this->openApiSpec !== null);
    }
}
