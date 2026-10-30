<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Fetch\Validation\JsonSchemaValidator;
use Fetch\Validation\ValidationConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * Covers JSON Schema normalization, references, and validation errors.
 */
final class JsonSchemaValidatorTest extends TestCase
{
    public function test_nested_schema_constraints(): void
    {
        $validator = new JsonSchemaValidator;
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'required' => ['name', 'items'],
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 2],
                'items' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer', 'minimum' => 1],
                ],
            ],
        ];

        $valid = $validator->validate(
            ['name' => 'Ada', 'items' => [1, 2]],
            $schema,
        );
        $invalid = $validator->validate(
            ['name' => 123, 'items' => [0, 'sensitive-value']],
            $schema,
        );

        self::assertTrue($valid->isValid());
        self::assertFalse($invalid->isValid());
        self::assertGreaterThanOrEqual(3, $invalid->count());
        $paths = array_map(
            static fn ($error): string => $error->pathString(),
            $invalid->errors(),
        );
        self::assertContains('/name', $paths);
        self::assertArrayHasKey('keyword', $invalid->errors()[0]->constraint);
        self::assertStringNotContainsString(
            'sensitive-value',
            $invalid->errors()[0]->message,
        );
    }

    public function test_it_supports_local_references(): void
    {
        $result = (new JsonSchemaValidator)->validate(
            ['address' => ['city' => 'Colombo']],
            [
                '$defs' => [
                    'address' => [
                        'type' => 'object',
                        'required' => ['city'],
                        'properties' => ['city' => ['type' => 'string']],
                    ],
                ],
                'type' => 'object',
                'properties' => ['address' => ['$ref' => '#/$defs/address']],
            ],
        );

        self::assertTrue($result->isValid());
    }

    public function test_rejects_untrusted_references_without_io(): void
    {
        $this->expectException(ValidationConfigurationException::class);
        $this->expectExceptionMessage(
            'register the referenced schema as trusted',
        );

        (new JsonSchemaValidator)->validate(
            ['name' => 'Ada'],
            ['$ref' => 'https://schemas.example.invalid/user.json'],
        );
    }

    public function test_rejects_untrusted_dynamic_references(): void
    {
        $this->expectException(ValidationConfigurationException::class);
        $this->expectExceptionMessage(
            'register the referenced schema as trusted',
        );

        (new JsonSchemaValidator)->validate(
            ['name' => 'Ada'],
            ['$dynamicRef' => 'https://schemas.example.invalid/user.json'],
        );
    }

    public function test_resolves_registered_trusted_schema(): void
    {
        $validator = new JsonSchemaValidator;
        $validator->registerTrustedSchema(
            'https://schemas.example.test/address.json',
            [
                'type' => 'object',
                'required' => ['city'],
                'properties' => ['city' => ['type' => 'string']],
            ],
        );

        $result = $validator->validate(
            ['city' => 'Colombo'],
            ['$ref' => 'https://schemas.example.test/address.json'],
        );

        self::assertTrue($result->isValid());
    }

    public function test_invalid_schema_is_a_configuration_error(): void
    {
        $this->expectException(ValidationConfigurationException::class);
        $this->expectExceptionMessage('must contain valid JSON');

        (new JsonSchemaValidator)->validate([], '{invalid');
    }
}
