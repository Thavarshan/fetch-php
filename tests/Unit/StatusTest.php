<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Checks status metadata, enum coverage, and conversion of unknown codes.
 */
class StatusTest extends TestCase
{
    #[DataProvider('status_cases')]
    public function test_status_metadata_matches_reference_data(
        Status $status,
        int $code,
        string $phrase,
        int $category,
        bool $cacheable,
    ): void {
        self::assertSame($code, $status->value);
        self::assertSame($phrase, $status->phrase());
        self::assertSame($category === 1, $status->isInformational());
        self::assertSame($category === 2, $status->isSuccess());
        self::assertSame($category === 3, $status->isRedirection());
        self::assertSame($category === 4, $status->isClientError());
        self::assertSame($category === 5, $status->isServerError());
        self::assertSame($category >= 4, $status->isError());
        self::assertSame($cacheable, $status->isCacheable());
        self::assertSame($status, Status::fromInt($code));
        self::assertSame($status, Status::tryFromInt($code));
    }

    public function test_reference_data_covers_every_enum_case(): void
    {
        self::assertSame(
            array_map(
                static fn (Status $status): string => $status->name,
                Status::cases(),
            ),
            array_keys(self::status_cases()),
        );
    }

    public function test_conversion_methods_handle_unknown_codes(): void
    {
        self::assertNull(Status::tryFromInt(999));
        self::assertSame(
            Status::NOT_FOUND,
            Status::tryFromInt(999, Status::NOT_FOUND),
        );

        $this->expectException(ValueError::class);
        Status::fromInt(999);
    }

    public function test_empty_and_not_modified_helpers(): void
    {
        self::assertTrue(Status::NO_CONTENT->isEmpty());
        self::assertTrue(Status::NOT_MODIFIED->isEmpty());
        self::assertFalse(Status::OK->isEmpty());
        self::assertTrue(Status::NOT_MODIFIED->isNotModified());
        self::assertFalse(Status::OK->isNotModified());
    }

    /**
     * Independent reference values for the currently supported statuses.
     *
     * @return array<string, array{Status, int, string, int, bool}>
     */
    public static function status_cases(): array
    {
        return [
            'CONTINUE' => [Status::CONTINUE, 100, 'Continue', 1, false],
            'SWITCHING_PROTOCOLS' => [
                Status::SWITCHING_PROTOCOLS,
                101,
                'Switching Protocols',
                1,
                false,
            ],
            'PROCESSING' => [Status::PROCESSING, 102, 'Processing', 1, false],
            'EARLY_HINTS' => [
                Status::EARLY_HINTS,
                103,
                'Early Hints',
                1,
                false,
            ],
            'OK' => [Status::OK, 200, 'OK', 2, true],
            'CREATED' => [Status::CREATED, 201, 'Created', 2, false],
            'ACCEPTED' => [Status::ACCEPTED, 202, 'Accepted', 2, false],
            'NON_AUTHORITATIVE_INFORMATION' => [
                Status::NON_AUTHORITATIVE_INFORMATION,
                203,
                'Non-Authoritative Information',
                2,
                true,
            ],
            'NO_CONTENT' => [Status::NO_CONTENT, 204, 'No Content', 2, false],
            'RESET_CONTENT' => [
                Status::RESET_CONTENT,
                205,
                'Reset Content',
                2,
                false,
            ],
            'PARTIAL_CONTENT' => [
                Status::PARTIAL_CONTENT,
                206,
                'Partial Content',
                2,
                true,
            ],
            'MULTI_STATUS' => [
                Status::MULTI_STATUS,
                207,
                'Multi-Status',
                2,
                false,
            ],
            'ALREADY_REPORTED' => [
                Status::ALREADY_REPORTED,
                208,
                'Already Reported',
                2,
                false,
            ],
            'IM_USED' => [Status::IM_USED, 226, 'IM Used', 2, false],
            'MULTIPLE_CHOICES' => [
                Status::MULTIPLE_CHOICES,
                300,
                'Multiple Choices',
                3,
                true,
            ],
            'MOVED_PERMANENTLY' => [
                Status::MOVED_PERMANENTLY,
                301,
                'Moved Permanently',
                3,
                true,
            ],
            'FOUND' => [Status::FOUND, 302, 'Found', 3, false],
            'SEE_OTHER' => [Status::SEE_OTHER, 303, 'See Other', 3, false],
            'NOT_MODIFIED' => [
                Status::NOT_MODIFIED,
                304,
                'Not Modified',
                3,
                false,
            ],
            'USE_PROXY' => [Status::USE_PROXY, 305, 'Use Proxy', 3, false],
            'TEMPORARY_REDIRECT' => [
                Status::TEMPORARY_REDIRECT,
                307,
                'Temporary Redirect',
                3,
                false,
            ],
            'PERMANENT_REDIRECT' => [
                Status::PERMANENT_REDIRECT,
                308,
                'Permanent Redirect',
                3,
                false,
            ],
            'BAD_REQUEST' => [
                Status::BAD_REQUEST,
                400,
                'Bad Request',
                4,
                false,
            ],
            'UNAUTHORIZED' => [
                Status::UNAUTHORIZED,
                401,
                'Unauthorized',
                4,
                false,
            ],
            'PAYMENT_REQUIRED' => [
                Status::PAYMENT_REQUIRED,
                402,
                'Payment Required',
                4,
                false,
            ],
            'FORBIDDEN' => [Status::FORBIDDEN, 403, 'Forbidden', 4, false],
            'NOT_FOUND' => [Status::NOT_FOUND, 404, 'Not Found', 4, true],
            'METHOD_NOT_ALLOWED' => [
                Status::METHOD_NOT_ALLOWED,
                405,
                'Method Not Allowed',
                4,
                true,
            ],
            'NOT_ACCEPTABLE' => [
                Status::NOT_ACCEPTABLE,
                406,
                'Not Acceptable',
                4,
                false,
            ],
            'PROXY_AUTHENTICATION_REQUIRED' => [
                Status::PROXY_AUTHENTICATION_REQUIRED,
                407,
                'Proxy Authentication Required',
                4,
                false,
            ],
            'REQUEST_TIMEOUT' => [
                Status::REQUEST_TIMEOUT,
                408,
                'Request Timeout',
                4,
                false,
            ],
            'CONFLICT' => [Status::CONFLICT, 409, 'Conflict', 4, false],
            'GONE' => [Status::GONE, 410, 'Gone', 4, true],
            'LENGTH_REQUIRED' => [
                Status::LENGTH_REQUIRED,
                411,
                'Length Required',
                4,
                false,
            ],
            'PRECONDITION_FAILED' => [
                Status::PRECONDITION_FAILED,
                412,
                'Precondition Failed',
                4,
                false,
            ],
            'PAYLOAD_TOO_LARGE' => [
                Status::PAYLOAD_TOO_LARGE,
                413,
                'Payload Too Large',
                4,
                false,
            ],
            'URI_TOO_LONG' => [
                Status::URI_TOO_LONG,
                414,
                'URI Too Long',
                4,
                false,
            ],
            'UNSUPPORTED_MEDIA_TYPE' => [
                Status::UNSUPPORTED_MEDIA_TYPE,
                415,
                'Unsupported Media Type',
                4,
                false,
            ],
            'RANGE_NOT_SATISFIABLE' => [
                Status::RANGE_NOT_SATISFIABLE,
                416,
                'Range Not Satisfiable',
                4,
                false,
            ],
            'EXPECTATION_FAILED' => [
                Status::EXPECTATION_FAILED,
                417,
                'Expectation Failed',
                4,
                false,
            ],
            'IM_A_TEAPOT' => [
                Status::IM_A_TEAPOT,
                418,
                "I'm a teapot",
                4,
                false,
            ],
            'MISDIRECTED_REQUEST' => [
                Status::MISDIRECTED_REQUEST,
                421,
                'Misdirected Request',
                4,
                false,
            ],
            'UNPROCESSABLE_ENTITY' => [
                Status::UNPROCESSABLE_ENTITY,
                422,
                'Unprocessable Entity',
                4,
                false,
            ],
            'LOCKED' => [Status::LOCKED, 423, 'Locked', 4, false],
            'FAILED_DEPENDENCY' => [
                Status::FAILED_DEPENDENCY,
                424,
                'Failed Dependency',
                4,
                false,
            ],
            'TOO_EARLY' => [Status::TOO_EARLY, 425, 'Too Early', 4, false],
            'UPGRADE_REQUIRED' => [
                Status::UPGRADE_REQUIRED,
                426,
                'Upgrade Required',
                4,
                false,
            ],
            'PRECONDITION_REQUIRED' => [
                Status::PRECONDITION_REQUIRED,
                428,
                'Precondition Required',
                4,
                false,
            ],
            'TOO_MANY_REQUESTS' => [
                Status::TOO_MANY_REQUESTS,
                429,
                'Too Many Requests',
                4,
                false,
            ],
            'REQUEST_HEADER_FIELDS_TOO_LARGE' => [
                Status::REQUEST_HEADER_FIELDS_TOO_LARGE,
                431,
                'Request Header Fields Too Large',
                4,
                false,
            ],
            'UNAVAILABLE_FOR_LEGAL_REASONS' => [
                Status::UNAVAILABLE_FOR_LEGAL_REASONS,
                451,
                'Unavailable For Legal Reasons',
                4,
                false,
            ],
            'INTERNAL_SERVER_ERROR' => [
                Status::INTERNAL_SERVER_ERROR,
                500,
                'Internal Server Error',
                5,
                false,
            ],
            'NOT_IMPLEMENTED' => [
                Status::NOT_IMPLEMENTED,
                501,
                'Not Implemented',
                5,
                false,
            ],
            'BAD_GATEWAY' => [
                Status::BAD_GATEWAY,
                502,
                'Bad Gateway',
                5,
                false,
            ],
            'SERVICE_UNAVAILABLE' => [
                Status::SERVICE_UNAVAILABLE,
                503,
                'Service Unavailable',
                5,
                false,
            ],
            'GATEWAY_TIMEOUT' => [
                Status::GATEWAY_TIMEOUT,
                504,
                'Gateway Timeout',
                5,
                false,
            ],
            'HTTP_VERSION_NOT_SUPPORTED' => [
                Status::HTTP_VERSION_NOT_SUPPORTED,
                505,
                'HTTP Version Not Supported',
                5,
                false,
            ],
            'VARIANT_ALSO_NEGOTIATES' => [
                Status::VARIANT_ALSO_NEGOTIATES,
                506,
                'Variant Also Negotiates',
                5,
                false,
            ],
            'INSUFFICIENT_STORAGE' => [
                Status::INSUFFICIENT_STORAGE,
                507,
                'Insufficient Storage',
                5,
                false,
            ],
            'LOOP_DETECTED' => [
                Status::LOOP_DETECTED,
                508,
                'Loop Detected',
                5,
                false,
            ],
            'NOT_EXTENDED' => [
                Status::NOT_EXTENDED,
                510,
                'Not Extended',
                5,
                false,
            ],
            'NETWORK_AUTHENTICATION_REQUIRED' => [
                Status::NETWORK_AUTHENTICATION_REQUIRED,
                511,
                'Network Authentication Required',
                5,
                false,
            ],
        ];
    }
}
