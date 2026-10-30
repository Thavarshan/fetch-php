---
title: Request & Response Validation
description: Validate JSON payloads and OpenAPI 3.0 contracts in Fetch PHP
---

# Request & Response Validation

Fetch PHP offers opt-in JSON Schema validation and OpenAPI 3.0.x contract
validation. Normal requests do not load either validator unless a validation
policy is configured.

## Optional packages

Install the adapters you use:

```sh
composer require opis/json-schema:^2.6 league/openapi-psr7-validator:^0.24
```

OpenAPI JSON documents need no YAML parser. To load YAML documents, install
`symfony/yaml:^6.0|^7.0` or enable the PHP `yaml` extension. The validator
dependencies are optional at runtime; invoking a missing adapter raises a
`ValidationDependencyException` with the package name to install.

## JSON Schema

Configure request and response schemas fluently on `ClientHandler`:

```php
use Fetch\Http\ClientHandler;

$userSchema = [
    "type" => "object",
    "required" => ["id", "name"],
    "properties" => [
        "id" => ["type" => "integer"],
        "name" => ["type" => "string"],
    ],
];

$response = ClientHandler::createWithBaseUri("https://api.example.com")
    ->withResponseSchema($userSchema)
    ->get("/users/123");
```

Request rules can be configured separately with `withRequestSchema()`. You can
also provide schemas per request:

```php
$response = fetch("https://api.example.com/users", [
    "method" => "POST",
    "json" => ["name" => "Ada"],
    "validation" => [
        "request_schema" => $requestSchema,
        "response_schema" => $responseSchema,
    ],
]);
```

The Opis adapter supports JSON Schema draft 2020-12. Results expose immutable
`ValidationError` entries with a data path, safe message, and constraint
keyword. JSON schemas can be PHP arrays/objects or JSON text. Local references
within a schema are supported. External references are disabled unless their
identifiers and schema contents are explicitly registered as trusted with
`JsonSchemaValidator::registerTrustedSchema()`; validation does not fetch remote
schemas.

JSON payload decoding preserves the distinction between objects (`{}`) and
arrays (`[]`), including empty objects and objects with sequential numeric
property names. When request validation is enabled, JSON request bodies supplied
as a resource or PSR-7 stream are read from their current position; seekable
positions are restored, and non-seekable bodies are made replayable for
transport dispatch. With request validation disabled, stream-backed uploads pass
through without being pre-read.

Raw JSON string bodies are validated from the same materialized value the
transport will send, even when middleware has already read its PSR-7 request
body. OpenAPI JSON parsing also preserves empty objects inside schemas and the
rest of the specification.

Invalid outgoing data raises `RequestValidationException` before transport
dispatch. Invalid response data raises `ResponseValidationException`. The
exceptions contain structured results and do not include the request or response
payload in their message.

## OpenAPI 3.0.x

Point the handler at a local JSON or YAML contract and enable contract checks:

```php
$response = ClientHandler::createWithBaseUri("https://api.example.com")
    ->withOpenApiSpec(__DIR__ . "/openapi.yaml")
    ->validateContracts()
    ->get("/users/123");
```

The adapter resolves the operation using the outgoing HTTP method and path, then
checks applicable parameters, request content, response status, headers, media
type, and body schema. JSON and YAML files are supported. Only OpenAPI 3.0.x is
accepted; 3.1 documents fail with a configuration exception rather than being
interpreted as 3.0. External `$ref` URLs are rejected to prevent implicit
network retrieval; references within the local document are supported.

Contract checks account for Guzzle's Basic `auth => [$username, $password]`
option, matching cookies from a `CookieJarInterface`, and its `query` option
replacing any query already in the URI. The resolved query is sent as part of
the request URI after validation.

Multipart requests are constructed with their generated boundary before contract
checks. Because validating multipart content reads the serialized body before
dispatch, enable this for uploads only when the buffering cost fits the expected
payload size.

## Lifecycle and streaming

Request validation runs after middleware has transformed the outgoing request
and before mock/cache/transport selection. Responses from middleware, mocks,
cache hits, and the transport are validated before being returned. Invalid
responses are not cached, and schema failures are not retried. OpenAPI response
validation uses the operation matched from the final outgoing request. Non-2xx
responses can be validated when declared in the contract.

Streaming responses and Server-Sent Events are intentionally not buffered for
schema validation. If a handler has any enabled validation policy, `stream()`
and `sse()` raise `ValidationConfigurationException` and explain that the stream
cannot be validated without buffering. Remove the validation policy for that
request when incremental streaming is required.

Validation does not sanitize or mutate payloads. It checks data and reports
errors; escaping, redaction, and transformations remain explicit application
responsibilities.

For an intentional structured-data transformation, use an opt-in middleware
callback. Request middleware runs before request validation, so the contract
checks the transformed body. Response middleware runs after the response
contract has been checked, then can map the response the application receives:

```php
use Fetch\Http\ClientHandler;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$handler = ClientHandler::create()
    ->withRequestSchema($requestSchema)
    ->withResponseSchema($responseSchema);

$handler->addMiddleware(static function (
    RequestInterface $request,
    callable $next,
): mixed {
    $response = $next($request);

    $mapResponse = static function (
        ResponseInterface $response,
    ): ResponseInterface {
        if (
            !str_contains(
                strtolower($response->getHeaderLine("Content-Type")),
                "json",
            )
        ) {
            return $response;
        }

        $data = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $data["name"] = trim($data["name"]);
        $body = Utils::streamFor(json_encode($data, JSON_THROW_ON_ERROR));

        return $response->withBody($body);
    };

    return $response instanceof \React\Promise\PromiseInterface
        ? $response->then($mapResponse)
        : $mapResponse($response);
});
```

Use explicit mapping callbacks or registered hydrators when converting decoded
data to DTOs. Fetch does not perform implicit object hydration or generic HTML
escaping.

## Runnable example

<<< @/examples/snippets/validation.php
