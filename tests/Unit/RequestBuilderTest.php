<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Http\RequestBuilder;
use Fetch\Support\RequestContext;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class RequestBuilderTest extends TestCase
{
    public function test_cookie_headers_are_validated_without_auth_options(): void
    {
        $builder = new RequestBuilder;
        $request = new Request('GET', 'https://example.test/secure');
        $cookies = CookieJar::fromArray(['session' => 'fixture'], 'example.test');

        $prepared = $builder->prepareValidationRequest(
            $request,
            ['cookies' => $cookies],
        );

        self::assertSame('session=fixture', $prepared->getHeaderLine('Cookie'));
        self::assertFalse($prepared->hasHeader('Authorization'));
        $otherHost = $builder->prepareValidationRequest(
            new Request('GET', 'https://other.test/secure'),
            ['cookies' => $cookies],
        );
        self::assertFalse($otherHost->hasHeader('Cookie'));
    }

    public function test_json_body_options_are_built_without_mutating_input(): void
    {
        $builder = new RequestBuilder;
        $options = ['headers' => ['X-Existing' => 'yes']];

        $result = $builder->applyBodyOptions($options, ['name' => 'Ada'], ContentType::JSON);

        self::assertSame(['headers' => ['X-Existing' => 'yes']], $options);
        self::assertSame(['name' => 'Ada'], $result['json']);
        self::assertSame('application/json', $result['headers']['Content-Type']);
    }

    public function test_middleware_body_replacement_removes_conflicting_structured_body_options(): void
    {
        $builder = new RequestBuilder;
        $request = new Request('POST', 'https://example.test/users', [], '{"name":"Ada"}');
        $request = $request->withBody(Utils::streamFor('{"name":"Grace"}'));

        $options = $builder->mergeMiddlewareRequest(
            $request,
            ['json' => ['name' => 'Ada'], 'headers' => ['Content-Type' => 'application/json']],
            '{"name":"Ada"}',
        );

        self::assertSame('{"name":"Grace"}', $options['body']);
        self::assertArrayNotHasKey('json', $options);
    }

    public function test_middleware_edits_to_a_materialized_body_stream_are_sent(): void
    {
        $builder = new RequestBuilder;
        $seedBody = '{"name":"Ada"}';
        $request = new Request('POST', 'https://example.test/users', ['Content-Type' => 'application/json'], $seedBody);
        $request->getBody()->write('{"name":"Grace"}');

        $options = $builder->mergeMiddlewareRequest(
            $request,
            ['json' => ['name' => 'Ada'], 'headers' => ['Content-Type' => 'application/json']],
            $seedBody,
            $request->getBody(),
        );

        self::assertSame('{"name":"Grace"}', $options['body']);
        self::assertArrayNotHasKey('json', $options);
    }

    public function test_multipart_middleware_request_has_a_matching_boundary_and_transport_body(): void
    {
        $builder = new RequestBuilder;
        $parts = [['name' => 'title', 'contents' => 'quarterly report']];
        [$request, $seedBody] = $builder->buildMiddlewareRequest(
            'POST',
            'https://example.test/upload',
            ['multipart' => $parts],
        );
        $boundary = substr($request->getHeaderLine('Content-Type'), strlen('multipart/form-data; boundary='));

        $options = $builder->mergeMiddlewareRequest($request, ['multipart' => $parts], $seedBody, $request->getBody());

        self::assertNull($seedBody);
        self::assertNotSame('', $boundary);
        self::assertSame($request->getBody(), $options['body']);
        self::assertArrayNotHasKey('multipart', $options);
        self::assertSame('multipart/form-data; boundary='.$boundary, $options['headers']['Content-Type']);
    }

    public function test_seed_body_reads_psr_stream_from_its_current_position_and_restores_it(): void
    {
        $body = Utils::streamFor('invalid-prefix{"name":"Ada"}');
        $body->seek(strlen('invalid-prefix'));

        $seedBody = (new RequestBuilder)->encodeSeedBody(['body' => $body]);

        self::assertSame('{"name":"Ada"}', $seedBody);
        self::assertSame(strlen('invalid-prefix'), $body->tell());
    }

    public function test_seed_body_reads_resource_from_its_current_position_and_restores_it(): void
    {
        $body = fopen('php://temp', 'r+');
        self::assertIsResource($body);
        fwrite($body, 'invalid-prefix{"name":"Ada"}');
        fseek($body, strlen('invalid-prefix'));

        $seedBody = (new RequestBuilder)->encodeSeedBody(['body' => $body]);

        self::assertSame('{"name":"Ada"}', $seedBody);
        self::assertSame(strlen('invalid-prefix'), ftell($body));
        fclose($body);
    }

    public function test_query_options_replace_existing_query_like_guzzle(): void
    {
        $builder = new RequestBuilder;
        $request = new Request('GET', 'https://example.test/users?existing=1');

        $withQuery = $builder->withQueryOptions($request, ['query' => ['page' => 2]]);

        self::assertSame('page=2', $withQuery->getUri()->getQuery());
    }

    public function test_query_options_use_guzzles_rfc3986_encoding(): void
    {
        $builder = new RequestBuilder;
        $request = new Request('GET', 'https://example.test/users?old=yes');

        $withQuery = $builder->withQueryOptions($request, ['query' => ['search' => 'two words']]);

        self::assertSame('search=two%20words', $withQuery->getUri()->getQuery());
    }

    public function test_basic_auth_options_are_materialized_for_validation(): void
    {
        $builder = new RequestBuilder;
        $request = new Request('GET', 'https://example.test/secure');

        $withAuth = $builder->prepareValidationRequest($request, ['auth' => ['fixture-user', 'fixture-password']]);

        self::assertSame(
            'Basic '.base64_encode('fixture-user:fixture-password'),
            $withAuth->getHeaderLine('Authorization'),
        );
    }

    public function test_prepares_only_supported_guzzle_options_from_request_context(): void
    {
        $builder = new RequestBuilder;
        $context = RequestContext::fromOptions(['method' => 'POST', 'uri' => '/users', 'json' => ['x' => 1], 'validation' => ['response_schema' => ['type' => 'object']]]);

        $options = $builder->prepareGuzzleOptions([], $context);

        self::assertArrayHasKey('json', $options);
        self::assertArrayNotHasKey('validation', $options);
    }
}
