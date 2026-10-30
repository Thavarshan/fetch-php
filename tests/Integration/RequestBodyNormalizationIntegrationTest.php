<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Tests\TestCase;

final class RequestBodyNormalizationIntegrationTest extends TestCase
{
    public function test_fluent_body_transitions_match_the_outgoing_request(
    ): void {
        $history = [];
        $handler = $this->createHandler($history, 6);

        $handler
            ->withFormParams(['name' => 'John'])
            ->withJson(['name' => 'Jane'])
            ->sendRequest(Method::POST, 'https://example.test/json');

        $handler
            ->withJson(['old' => true])
            ->withBody(
                ['name' => 'Ada'],
                ContentType::FORM_URLENCODED,
            )
            ->sendRequest(Method::POST, 'https://example.test/form');

        $handler
            ->withHeader('content-type', 'multipart/form-data')
            ->withBody(
                [['name' => 'upload', 'contents' => 'fixture']],
                ContentType::MULTIPART,
            )
            ->sendRequest(Method::POST, 'https://example.test/multipart');

        $handler
            ->withBody('plain payload', ContentType::TEXT)
            ->sendRequest(Method::POST, 'https://example.test/raw');

        $handler
            ->withJson(['old' => true])
            ->withOption('body', null)
            ->sendRequest(Method::POST, 'https://example.test/empty');

        $handler
            ->withJson(['old' => true])
            ->sendRequest(
                Method::POST,
                'https://example.test/request-form',
                ['form_params' => ['name' => 'Grace']],
            );

        self::assertSame(
            '{"name":"Jane"}',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            ContentType::JSON->value,
            $history[0]['request']->getHeaderLine('Content-Type'),
        );

        self::assertSame(
            'name=Ada',
            (string) $history[1]['request']->getBody(),
        );
        self::assertSame(
            ContentType::FORM_URLENCODED->value,
            $history[1]['request']->getHeaderLine('Content-Type'),
        );

        $multipartRequest = $history[2]['request'];
        self::assertStringStartsWith(
            'multipart/form-data; boundary=',
            $multipartRequest->getHeaderLine('Content-Type'),
        );
        self::assertStringContainsString(
            'fixture',
            (string) $multipartRequest->getBody(),
        );

        self::assertSame(
            'plain payload',
            (string) $history[3]['request']->getBody(),
        );
        self::assertSame(
            ContentType::TEXT->value,
            $history[3]['request']->getHeaderLine('Content-Type'),
        );

        self::assertSame('', (string) $history[4]['request']->getBody());
        self::assertFalse($history[4]['request']->hasHeader('Content-Type'));

        self::assertSame(
            'name=Grace',
            (string) $history[5]['request']->getBody(),
        );
        self::assertSame(
            ContentType::FORM_URLENCODED->value,
            $history[5]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_client_fetch_preserves_custom_form_encoding(): void
    {
        $history = [];
        $client = new Client($this->createHandler($history, 1));

        $client->fetch('https://example.test/client', [
            'method' => 'POST',
            'form_params' => ['name' => 'Ada'],
            'headers' => ['Content-Type' => 'text/plain'],
        ]);

        self::assertSame(
            'name=Ada',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_client_post_does_not_replace_a_custom_header(): void
    {
        $history = [];
        $client = new Client($this->createHandler($history, 1));

        $client->post(
            'https://example.test/client-post',
            'plain payload',
            ContentType::JSON,
            ['headers' => ['content-type' => 'text/plain']],
        );

        self::assertSame(
            'plain payload',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_handler_post_preserves_configured_custom_header(): void
    {
        $history = [];
        $handler = $this->createHandler($history, 1);

        $handler
            ->withHeader('Content-Type', 'text/plain')
            ->post('https://example.test/handler-post', 'plain payload');

        self::assertSame(
            'plain payload',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_global_fetch_preserves_custom_form_encoding(): void
    {
        $history = [];
        $handler = $this->createHandler($history, 1);
        fetch_client()->getHandler()->setHttpClient($handler->getHttpClient());

        fetch('https://example.test/global', [
            'method' => 'POST',
            'form_params' => ['name' => 'Ada'],
            'headers' => ['content-type' => 'text/plain'],
        ]);

        self::assertSame(
            'name=Ada',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_request_options_keep_custom_type_and_form_encoding(
    ): void {
        $history = [];
        $handler = $this->createHandler($history, 1);

        $handler->sendRequest(Method::POST, 'https://example.test/options', [
            'form_params' => ['name' => 'Ada'],
            'headers' => ['Content-Type' => 'text/plain'],
        ]);

        self::assertSame(
            'name=Ada',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_request_builder_preserves_explicit_custom_content_type(
    ): void {
        $history = [];
        $handler = $this->createHandler($history, 1);

        $handler->request(
            Method::POST,
            'https://example.test/builder',
            ['name' => 'Ada'],
            ContentType::JSON,
            ['headers' => ['Content-Type' => 'text/plain']],
        );

        self::assertSame(
            '{"name":"Ada"}',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_custom_headers_do_not_change_selected_body_encoding(
    ): void {
        $history = [];
        $handler = $this->createHandler($history, 3);

        $handler
            ->withHeader('Content-Type', 'text/plain')
            ->post(
                'https://example.test/handler-form',
                ['name' => 'Ada'],
                ContentType::FORM_URLENCODED,
            );

        $handler->request(
            Method::POST,
            'https://example.test/request-form',
            ['name' => 'Grace'],
            ContentType::FORM_URLENCODED,
            ['headers' => ['Content-Type' => 'text/plain']],
        );

        $handler->post(
            'https://example.test/handler-multipart',
            [['name' => 'upload', 'contents' => 'fixture']],
            ContentType::MULTIPART,
        );

        self::assertSame(
            'name=Ada',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );

        self::assertSame(
            'name=Grace',
            (string) $history[1]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[1]['request']->getHeaderLine('Content-Type'),
        );

        $multipartRequest = $history[2]['request'];
        self::assertStringStartsWith(
            'multipart/form-data; boundary=',
            $multipartRequest->getHeaderLine('Content-Type'),
        );
        self::assertStringContainsString(
            'fixture',
            (string) $multipartRequest->getBody(),
        );
    }

    public function test_client_verb_helpers_keep_selected_body_encoding(
    ): void {
        $history = [];
        $client = new Client($this->createHandler($history, 3));

        $client->post(
            'https://example.test/client-form',
            ['name' => 'Ada'],
            ContentType::FORM_URLENCODED,
            ['headers' => ['Content-Type' => 'text/plain']],
        );

        $client->post(
            'https://example.test/client-multipart',
            [['name' => 'upload', 'contents' => 'fixture']],
            ContentType::MULTIPART,
            ['headers' => ['Content-Type' => 'text/plain']],
        );

        $client->post(
            'https://example.test/client-option-form',
            ['name' => 'Grace'],
            ContentType::JSON,
            [
                'content_type' => ContentType::FORM_URLENCODED,
                'headers' => ['Content-Type' => 'text/plain'],
            ],
        );

        self::assertSame(
            'name=Ada',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );

        $multipartRequest = $history[1]['request'];
        self::assertStringStartsWith(
            'multipart/form-data; boundary=',
            $multipartRequest->getHeaderLine('Content-Type'),
        );
        self::assertStringContainsString(
            'fixture',
            (string) $multipartRequest->getBody(),
        );

        self::assertSame(
            'name=Grace',
            (string) $history[2]['request']->getBody(),
        );
        self::assertSame(
            ContentType::FORM_URLENCODED->value,
            $history[2]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_fluent_body_format_is_independent_of_custom_header(
    ): void {
        $history = [];
        $handler = $this->createHandler($history, 2);

        $handler
            ->withHeader('Content-Type', 'text/plain')
            ->withBody(
                ['name' => 'Ada'],
                ContentType::FORM_URLENCODED,
            )
            ->sendRequest(Method::POST, 'https://example.test/fluent-form');

        $handler
            ->withHeader(
                'Content-Type',
                ContentType::FORM_URLENCODED->value,
            )
            ->withBody(['name' => 'Grace'], ContentType::JSON)
            ->sendRequest(Method::POST, 'https://example.test/fluent-json');

        self::assertSame(
            'name=Ada',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            'text/plain',
            $history[0]['request']->getHeaderLine('Content-Type'),
        );

        self::assertSame(
            '{"name":"Grace"}',
            (string) $history[1]['request']->getBody(),
        );
        self::assertSame(
            ContentType::FORM_URLENCODED->value,
            $history[1]['request']->getHeaderLine('Content-Type'),
        );
    }

    public function test_content_type_option_survives_header_configuration(
    ): void {
        $history = [];
        $handler = $this->createHandler($history, 2);

        $handler
            ->withOption('content_type', ContentType::TEXT)
            ->withHeader('Accept', 'application/json')
            ->withBody('hello')
            ->sendRequest(Method::POST, 'https://example.test/pending-type');

        $handler
            ->reset()
            ->withOption(
                'content_type',
                ContentType::FORM_URLENCODED,
            )
            ->withHeader('Accept', 'application/json')
            ->sendRequest(
                Method::POST,
                'https://example.test/pending-form',
                ['body' => ['name' => 'Ada']],
            );

        self::assertSame(
            'hello',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(
            ContentType::TEXT->value,
            $history[0]['request']->getHeaderLine('Content-Type'),
        );

        self::assertSame(
            'name=Ada',
            (string) $history[1]['request']->getBody(),
        );
        self::assertSame(
            ContentType::FORM_URLENCODED->value,
            $history[1]['request']->getHeaderLine('Content-Type'),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     */
    private function createHandler(
        array &$history,
        int $responses,
    ): ClientHandler {
        $stack = HandlerStack::create(new MockHandler(array_fill(
            0,
            $responses,
            new GuzzleResponse(200),
        )));
        $stack->push(Middleware::history($history));

        return ClientHandler::createWithClient(
            new GuzzleClient(['handler' => $stack]),
        );
    }
}
