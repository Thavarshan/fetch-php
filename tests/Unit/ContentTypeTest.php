<?php

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use PHPUnit\Framework\TestCase;

class ContentTypeTest extends TestCase
{
    public function test_from_string(): void
    {
        $this->assertEquals(ContentType::JSON, ContentType::fromString('application/json'));
        $this->assertEquals(ContentType::TEXT, ContentType::fromString('text/plain'));

        // Test case insensitivity
        $this->assertEquals(ContentType::JSON, ContentType::fromString('APPLICATION/JSON'));
    }

    public function test_from_string_throws_exception_for_invalid_content_type(): void
    {
        $this->expectException(\ValueError::class);
        ContentType::fromString('invalid/content-type');
    }

    public function test_try_from_string(): void
    {
        $this->assertEquals(ContentType::JSON, ContentType::tryFromString('application/json'));

        // Test with invalid content type and default
        $this->assertEquals(ContentType::JSON, ContentType::tryFromString('invalid/content-type', ContentType::JSON));
        $this->assertNull(ContentType::tryFromString('invalid/content-type'));
    }

    public function test_is_json(): void
    {
        $this->assertTrue(ContentType::JSON->isJson());
        $this->assertFalse(ContentType::TEXT->isJson());
    }

    public function test_is_form(): void
    {
        $this->assertTrue(ContentType::FORM_URLENCODED->isForm());
        $this->assertFalse(ContentType::JSON->isForm());
    }

    public function test_is_multipart(): void
    {
        $this->assertTrue(ContentType::MULTIPART->isMultipart());
        $this->assertFalse(ContentType::JSON->isMultipart());
    }

    public function test_is_text(): void
    {
        $this->assertTrue(ContentType::JSON->isText());
        $this->assertTrue(ContentType::TEXT->isText());
        $this->assertFalse(ContentType::MULTIPART->isText());
    }

    public function test_normalize_content_type(): void
    {
        // Test with ContentType instance
        $contentType = ContentType::JSON;
        $this->assertSame($contentType, ContentType::normalizeContentType($contentType));

        // Test with valid string
        $this->assertEquals(ContentType::JSON, ContentType::normalizeContentType('application/json'));

        // Test with invalid string
        $invalidType = 'invalid/content-type';
        $this->assertSame($invalidType, ContentType::normalizeContentType($invalidType));
    }
}
