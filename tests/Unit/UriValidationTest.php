<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UriValidationTest extends TestCase
{
    private ClientHandler $handler;

    protected function setUp(): void
    {
        // Create anonymous class to expose protected methods
        $this->handler = new class extends ClientHandler
        {
            public function exposeValidateUriString(string $uri): void
            {
                $this->validateUriString($uri);
            }

            public function exposeIsValidUriString(string $uri): bool
            {
                return $this->isValidUriString($uri);
            }

            public function exposeValidateUriInputs(string $uri, string $baseUri): void
            {
                $this->validateUriInputs($uri, $baseUri);
            }
        };
    }

    public function test_validate_uri_string_accepts_valid_uris(): void
    {
        // Should not throw for valid URIs
        $this->handler->exposeValidateUriString('https://example.com');
        $this->handler->exposeValidateUriString('/api/users');
        $this->handler->exposeValidateUriString('/api/users?page=1');
        $this->handler->exposeValidateUriString('http://example.com/path/to/resource');
        $this->handler->exposeValidateUriString('/path/with-dashes_and_underscores');

        $this->assertTrue(true); // Assertion to confirm no exception
    }

    public function test_validate_uri_string_rejects_empty_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot be empty or whitespace');

        $this->handler->exposeValidateUriString('');
    }

    public function test_validate_uri_string_rejects_whitespace_only_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot be empty or whitespace');

        $this->handler->exposeValidateUriString('   ');
    }

    public function test_validate_uri_string_rejects_uri_with_spaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot contain whitespace. Did you mean to URL-encode it?');

        $this->handler->exposeValidateUriString('/api/users with spaces');
    }

    public function test_validate_uri_string_rejects_uri_with_tab_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot contain whitespace');

        $this->handler->exposeValidateUriString("/api/users\twith\ttabs");
    }

    public function test_validate_uri_string_rejects_uri_with_newlines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot contain whitespace');

        $this->handler->exposeValidateUriString("/api/users\nwith\nnewlines");
    }

    public function test_is_valid_uri_string_returns_true_for_valid_uris(): void
    {
        $this->assertTrue($this->handler->exposeIsValidUriString('https://example.com'));
        $this->assertTrue($this->handler->exposeIsValidUriString('/api/users'));
        $this->assertTrue($this->handler->exposeIsValidUriString('/api/users?page=1'));
    }

    public function test_is_valid_uri_string_returns_false_for_invalid_uris(): void
    {
        $this->assertFalse($this->handler->exposeIsValidUriString(''));
        $this->assertFalse($this->handler->exposeIsValidUriString('   '));
        $this->assertFalse($this->handler->exposeIsValidUriString('/bad uri with spaces'));
        $this->assertFalse($this->handler->exposeIsValidUriString("/uri\twith\ttabs"));
    }

    public function test_validate_uri_inputs_rejects_empty_uri_and_base_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot be empty');

        $this->handler->exposeValidateUriInputs('', '');
    }

    public function test_validate_uri_inputs_rejects_relative_uri_without_base_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Relative URI '/api/users' cannot be used without a base URI");

        $this->handler->exposeValidateUriInputs('/api/users', '');
    }

    public function test_validate_uri_inputs_rejects_invalid_base_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base URI');

        $this->handler->exposeValidateUriInputs('/api/users', '/not-absolute');
    }

    public function test_validate_uri_inputs_accepts_valid_absolute_uri(): void
    {
        // Should not throw
        $this->handler->exposeValidateUriInputs('https://example.com/api/users', '');

        $this->assertTrue(true);
    }

    public function test_validate_uri_inputs_accepts_valid_relative_uri_with_base(): void
    {
        // Should not throw
        $this->handler->exposeValidateUriInputs('/api/users', 'https://example.com');

        $this->assertTrue(true);
    }

    public function test_validate_uri_inputs_rejects_uri_with_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot contain whitespace');

        $this->handler->exposeValidateUriInputs('/api/bad uri', 'https://example.com');
    }

    public function test_validate_uri_inputs_rejects_base_uri_with_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot contain whitespace');

        $this->handler->exposeValidateUriInputs('/api/users', 'https://example.com/bad base');
    }
}
