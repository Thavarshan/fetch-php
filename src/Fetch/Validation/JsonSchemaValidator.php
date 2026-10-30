<?php

declare(strict_types=1);

namespace Fetch\Validation;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError as OpisValidationError;
use Opis\JsonSchema\Parsers\SchemaParser;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator;
use Throwable;

final class JsonSchemaValidator implements ValidatorInterface
{
    private Validator $validator;

    /** @var array<string> */
    private array $trustedSchemaIds = [];

    public function __construct()
    {
        if (! class_exists(Validator::class)) {
            throw new ValidationDependencyException(
                'JSON Schema validation requires opis/json-schema:^2.6. '
                    .'Install it to use JsonSchemaValidator.',
            );
        }

        $this->validator = new Validator(
            new SchemaLoader(new SchemaParser, new SchemaResolver),
            max_errors: 50,
            stop_at_first_error: false,
        );
    }

    /**
     * Register a caller-approved local or remote identifier to an in-memory
     * schema.
     * No URI protocol handlers are registered, so validation never fetches
     * schemas.
     *
     * @param  array<string, mixed>|object|string|bool  $schema
     */
    public function registerTrustedSchema(
        string $id,
        array|object|string|bool $schema,
    ): void {
        $resolver = $this->validator->loader()->resolver();
        $rawSchema = is_array($schema)
            ? $this->normalizeSchema($schema)
            : $schema;
        if (
            ! ($resolver instanceof SchemaResolver) ||
            ! $resolver->registerRaw($rawSchema, $id)
        ) {
            throw new ValidationConfigurationException(
                'The trusted schema could not be registered.',
            );
        }

        $this->trustedSchemaIds[] = rtrim($id, '#');
    }

    public function validate(mixed $data, mixed $schema): ValidationResult
    {
        $normalizedSchema = $this->normalizeSchema($schema);
        $this->assertReferencesAreTrusted($normalizedSchema);

        try {
            $result = $this->validator->validate(
                $this->normalizeData($data),
                $normalizedSchema,
            );
        } catch (Throwable $exception) {
            throw new ValidationConfigurationException(
                'The JSON Schema is invalid or cannot be resolved.',
                previous: $exception,
            );
        }

        if ($result->isValid()) {
            return ValidationResult::valid();
        }

        $rootError = $result->error();
        if ($rootError === null) {
            return ValidationResult::valid();
        }

        $errors = [];
        $formatter = new ErrorFormatter;
        foreach (
            $formatter->formatFlat(
                $rootError,
                static fn (
                    OpisValidationError $error,
                ): ValidationError => new ValidationError(
                    $error->data()->fullPath(),
                    sprintf(
                        'Value at "%s" failed the "%s" constraint.',
                        $formatter->formatErrorKey($error),
                        $error->keyword(),
                    ),
                    ['keyword' => $error->keyword()],
                ),
            ) as $error
        ) {
            if ($error instanceof ValidationError) {
                $errors[] = $error;
            }
        }

        return new ValidationResult($errors);
    }

    private function normalizeSchema(mixed $schema): mixed
    {
        if (is_string($schema)) {
            try {
                $decoded = json_decode(
                    $schema,
                    false,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (Throwable $exception) {
                throw new ValidationConfigurationException(
                    'JSON Schema strings must contain valid JSON.',
                    previous: $exception,
                );
            }

            if (! is_object($decoded) && ! is_bool($decoded)) {
                throw new ValidationConfigurationException(
                    'A JSON Schema must be a JSON object or boolean schema.',
                );
            }

            return $decoded;
        }

        if (is_bool($schema) || is_object($schema)) {
            return $schema;
        }

        if (is_array($schema)) {
            $normalized = $this->normalizeData($schema);
            if (is_object($normalized)) {
                return $normalized;
            }
        }

        throw new ValidationConfigurationException(
            'A JSON Schema must be an object, an associative array, JSON '
                .'text, or a boolean schema.',
        );
    }

    private function normalizeData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[$key] = $this->normalizeData($value);
        }

        return array_is_list($normalized) ? $normalized : (object) $normalized;
    }

    private function assertReferencesAreTrusted(mixed $schema): void
    {
        if (is_object($schema)) {
            $schema = get_object_vars($schema);
        }

        if (! is_array($schema)) {
            return;
        }

        foreach ($schema as $key => $value) {
            if (
                in_array($key, ['$ref', '$dynamicRef'], true) &&
                is_string($value) &&
                ! str_starts_with($value, '#')
            ) {
                $trusted = false;
                foreach ($this->trustedSchemaIds as $id) {
                    if ($value === $id || str_starts_with($value, $id.'#')) {
                        $trusted = true;
                        break;
                    }
                }

                if (! $trusted) {
                    throw new ValidationConfigurationException(
                        'External JSON Schema references are disabled; '
                            .'register the referenced schema '
                            .'as trusted first.',
                    );
                }
            }

            $this->assertReferencesAreTrusted($value);
        }
    }
}
