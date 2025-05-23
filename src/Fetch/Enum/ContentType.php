<?php

declare(strict_types=1);

namespace Fetch\Enum;

enum ContentType: string
{
    case JSON = 'application/json';
    case FORM_URLENCODED = 'application/x-www-form-urlencoded';
    case MULTIPART = 'multipart/form-data';
    case TEXT = 'text/plain';
    case HTML = 'text/html';
    case XML = 'application/xml';
    case XML_TEXT = 'text/xml';
    case BINARY = 'application/octet-stream';
    case PDF = 'application/pdf';
    case CSV = 'text/csv';
    case ZIP = 'application/zip';
    case JAVASCRIPT = 'application/javascript';
    case CSS = 'text/css';

    /**
     * Get a content type from a string.
     *
     * @throws \ValueError If the content type is invalid
     */
    public static function fromString(string $contentType): self
    {
        return self::from(strtolower($contentType));
    }

    /**
     * Try to get a content type from a string, or return default.
     */
    public static function tryFromString(string $contentType, ?self $default = null): ?self
    {
        return self::tryFrom(strtolower($contentType)) ?? $default;
    }

    /**
     * Normalize a content type to a ContentType enum value.
     */
    public static function normalizeContentType(string|ContentType $contentType): string|ContentType
    {
        if ($contentType instanceof ContentType) {
            return $contentType;
        }

        // Try to convert to enum without a default
        $result = self::tryFromString($contentType);

        // Return the enum if found, otherwise return the original string
        return $result !== null ? $result : $contentType;
    }

    /**
     * Check if the content type is JSON.
     */
    public function isJson(): bool
    {
        return $this === self::JSON;
    }

    /**
     * Check if the content type is a form.
     */
    public function isForm(): bool
    {
        return $this === self::FORM_URLENCODED;
    }

    /**
     * Check if the content type is multipart.
     */
    public function isMultipart(): bool
    {
        return $this === self::MULTIPART;
    }

    /**
     * Check if the content type is text-based.
     */
    public function isText(): bool
    {
        return match ($this) {
            // These are text-based content types
            self::JSON, self::FORM_URLENCODED, self::TEXT, self::HTML, self::XML, self::CSV => true,
            // These are binary/non-text content types
            self::MULTIPART => false,
            // Default for any new enum values added in the future
            default => false,
        };
    }
}
