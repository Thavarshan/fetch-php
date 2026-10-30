<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Fetch\Validation\OpenApiContractValidator;
use Fetch\Validation\ValidationConfigurationException;
use Fetch\Validation\ValidationException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class OpenApiContractValidatorTest extends TestCase
{
    public function test_validates_request_path_and_response_body(): void
    {
        $this->withSpec($this->spec(), function (string $path): void {
            $validator = new OpenApiContractValidator;
            $operation = $validator->validateRequest(
                new Request('GET', 'https://example.test/users/12'),
                $path,
            );
            $validator->validateResponse(
                $operation,
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    '{"id":"12"}',
                ),
                $path,
            );

            $this->assertSame('get', $operation->method());
            $this->assertSame('/users/{id}', $operation->path());
        });
    }

    public function test_it_accepts_json_openapi_documents(): void
    {
        $spec = json_encode(
            [
                'openapi' => '3.0.3',
                'info' => ['title' => 'Health API', 'version' => '1.0.0'],
                'paths' => [
                    '/health' => [
                        'get' => [
                            'responses' => [
                                '200' => ['description' => 'Healthy'],
                            ],
                        ],
                    ],
                ],
            ],
            JSON_THROW_ON_ERROR,
        );

        $this->withSpec($spec, function (string $path): void {
            $validator = new OpenApiContractValidator;
            $operation = $validator->validateRequest(
                new Request('GET', 'https://example.test/health'),
                $path,
            );
            $validator->validateResponse($operation, new Response(200), $path);

            self::assertSame('/health', $operation->path());
        });
    }

    public function test_json_spec_preserves_empty_schema_objects(): void
    {
        $spec = <<<'JSON'
        {
          "openapi": "3.0.3",
          "info": {
            "title": "Labels API",
            "version": "1.0.0"
          },
          "paths": {
            "/labels": {
              "post": {
                "requestBody": {
                  "required": true,
                  "content": {
                    "application/json": {
                      "schema": {
                        "type": "object",
                        "additionalProperties": {}
                      }
                    }
                  }
                },
                "responses": {
                  "204": {
                    "description": "Accepted"
                  }
                }
              }
            }
          }
        }
        JSON;

        $this->withSpec($spec, function (string $path): void {
            $operation = (new OpenApiContractValidator)->validateRequest(
                new Request(
                    'POST',
                    'https://example.test/labels',
                    ['Content-Type' => 'application/json'],
                    '{"label":"accepted"}',
                ),
                $path,
            );

            self::assertSame('post', $operation->method());
        });
    }

    public function test_contract_mismatch_does_not_echo_payload(): void
    {
        $this->withSpec($this->spec(), function (string $path): void {
            $validator = new OpenApiContractValidator;
            $operation = $validator->validateRequest(
                new Request('GET', 'https://example.test/users/12'),
                $path,
            );

            try {
                $validator->validateResponse(
                    $operation,
                    new Response(
                        200,
                        ['Content-Type' => 'application/json'],
                        '{"id":12,"token":"secret-token"}',
                    ),
                    $path,
                );
                self::fail('A response with the wrong field type should fail.');
            } catch (ValidationException $exception) {
                self::assertStringNotContainsString(
                    'secret-token',
                    $exception->getMessage(),
                );
                self::assertFalse($exception->result()->isValid());
                self::assertSame(
                    ['body', 'id'],
                    $exception->result()->errors()[0]->path,
                );
                self::assertSame(
                    200,
                    $exception->result()->errors()[0]->constraint[
                        'status_code'
                    ],
                );
            }
        });
    }

    public function test_it_rejects_openapi_31_explicitly(): void
    {
        $this->withSpec(str_replace('3.0.3', '3.1.0', $this->spec()), function (
            string $path,
        ): void {
            $this->expectException(ValidationConfigurationException::class);
            $this->expectExceptionMessage('Only OpenAPI 3.0.x');

            (new OpenApiContractValidator)->validateRequest(
                new Request('GET', 'https://example.test/users/12'),
                $path,
            );
        });
    }

    public function test_rejects_external_references_before_parsing(): void
    {
        $spec = str_replace(
            'type: object',
            "type: object\n                "
                . "\$ref: 'https://schemas.example.invalid/user.json'",
            $this->spec(),
        );

        $this->withSpec($spec, function (string $path): void {
            $this->expectException(ValidationConfigurationException::class);
            $this->expectExceptionMessage(
                'External OpenAPI references are disabled',
            );

            (new OpenApiContractValidator)->validateRequest(
                new Request('GET', 'https://example.test/users/12'),
                $path,
            );
        });
    }

    private function withSpec(string $contents, callable $test): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fetch-openapi-');
        if ($path === false) {
            self::fail('Could not create a temporary OpenAPI file.');
        }

        try {
            file_put_contents($path, $contents);
            $test($path);
        } finally {
            unlink($path);
        }
    }

    private function spec(): string
    {
        return <<<'YAML'
        openapi: 3.0.3
        info:
          title: Test API
          version: 1.0.0
        paths:
          /users/{id}:
            get:
              parameters:
                - name: id
                  in: path
                  required: true
                  schema:
                    type: string
              responses:
                '200':
                  description: User found
                  content:
                    application/json:
                      schema:
                        type: object
                        required: [id]
                        properties:
                          id:
                            type: string
        YAML;
    }
}
