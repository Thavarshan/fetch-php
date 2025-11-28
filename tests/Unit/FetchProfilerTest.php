<?php

namespace Tests\Unit;

use Fetch\Support\FetchProfiler;
use PHPUnit\Framework\TestCase;

class FetchProfilerTest extends TestCase
{
    private FetchProfiler $profiler;

    protected function setUp(): void
    {
        $this->profiler = new FetchProfiler;
    }

    public function test_profiler_is_enabled_by_default(): void
    {
        $this->assertTrue($this->profiler->isEnabled());
    }

    public function test_can_enable_and_disable_profiler(): void
    {
        $this->profiler->disable();
        $this->assertFalse($this->profiler->isEnabled());

        $this->profiler->enable();
        $this->assertTrue($this->profiler->isEnabled());
    }

    public function test_start_profile_creates_entry(): void
    {
        $this->profiler->startProfile('test-request');

        $profile = $this->profiler->getProfile('test-request');

        $this->assertNotNull($profile);
        $this->assertEquals('test-request', $profile['request_id']);
        $this->assertFalse($profile['completed']);
    }

    public function test_start_profile_does_nothing_when_disabled(): void
    {
        $this->profiler->disable();
        $this->profiler->startProfile('test-request');

        $this->assertNull($this->profiler->getProfile('test-request'));
    }

    public function test_record_event(): void
    {
        $this->profiler->startProfile('test-request');
        $this->profiler->recordEvent('test-request', 'dns_start');
        $this->profiler->recordEvent('test-request', 'dns_end');

        $profile = $this->profiler->getProfile('test-request');

        $this->assertArrayHasKey('dns_start', $profile['events']);
        $this->assertArrayHasKey('dns_end', $profile['events']);
    }

    public function test_record_event_with_custom_timestamp(): void
    {
        $timestamp = 1234567890.123;

        $this->profiler->startProfile('test-request');
        $this->profiler->recordEvent('test-request', 'custom_event', $timestamp);

        $profile = $this->profiler->getProfile('test-request');

        $this->assertEquals($timestamp, $profile['events']['custom_event']);
    }

    public function test_record_event_does_nothing_for_nonexistent_request(): void
    {
        $this->profiler->recordEvent('nonexistent', 'event');

        $this->assertNull($this->profiler->getProfile('nonexistent'));
    }

    public function test_end_profile(): void
    {
        $this->profiler->startProfile('test-request');
        $this->profiler->endProfile('test-request', 200);

        $profile = $this->profiler->getProfile('test-request');

        $this->assertTrue($profile['completed']);
        $this->assertEquals(200, $profile['status_code']);
    }

    public function test_get_profile_returns_null_for_nonexistent(): void
    {
        $this->assertNull($this->profiler->getProfile('nonexistent'));
    }

    public function test_get_profile_calculates_metrics(): void
    {
        $this->profiler->startProfile('test-request');
        usleep(10000); // 10ms
        $this->profiler->endProfile('test-request', 200);

        $profile = $this->profiler->getProfile('test-request');

        $this->assertArrayHasKey('total_time', $profile);
        $this->assertArrayHasKey('memory_start', $profile);
        $this->assertArrayHasKey('memory_end', $profile);
        $this->assertArrayHasKey('memory_delta', $profile);
        $this->assertArrayHasKey('memory_peak', $profile);
        $this->assertGreaterThan(0, $profile['total_time']);
    }

    public function test_get_profile_calculates_timing_phases(): void
    {
        $this->profiler->startProfile('test-request');

        // Simulate DNS resolution
        $dnsStart = microtime(true);
        $this->profiler->recordEvent('test-request', 'dns_start', $dnsStart);
        usleep(5000);
        $this->profiler->recordEvent('test-request', 'dns_end', $dnsStart + 0.005);

        // Simulate connection
        $connectStart = microtime(true);
        $this->profiler->recordEvent('test-request', 'connect_start', $connectStart);
        usleep(5000);
        $this->profiler->recordEvent('test-request', 'connect_end', $connectStart + 0.005);

        $this->profiler->endProfile('test-request', 200);

        $profile = $this->profiler->getProfile('test-request');

        $this->assertArrayHasKey('dns_time', $profile);
        $this->assertArrayHasKey('connect_time', $profile);
        $this->assertGreaterThan(0, $profile['dns_time']);
        $this->assertGreaterThan(0, $profile['connect_time']);
    }

    public function test_get_all_profiles(): void
    {
        $this->profiler->startProfile('request-1');
        $this->profiler->endProfile('request-1', 200);

        $this->profiler->startProfile('request-2');
        $this->profiler->endProfile('request-2', 201);

        $profiles = $this->profiler->getAllProfiles();

        $this->assertCount(2, $profiles);
        $this->assertArrayHasKey('request-1', $profiles);
        $this->assertArrayHasKey('request-2', $profiles);
    }

    public function test_clear_profile(): void
    {
        $this->profiler->startProfile('test-request');
        $this->profiler->clearProfile('test-request');

        $this->assertNull($this->profiler->getProfile('test-request'));
    }

    public function test_clear_all(): void
    {
        $this->profiler->startProfile('request-1');
        $this->profiler->startProfile('request-2');
        $this->profiler->clearAll();

        $this->assertEmpty($this->profiler->getAllProfiles());
    }

    public function test_get_summary_with_no_profiles(): void
    {
        $summary = $this->profiler->getSummary();

        $this->assertEquals(0, $summary['total_requests']);
        $this->assertEquals(0, $summary['completed_requests']);
        $this->assertEquals(0, $summary['total_time']);
    }

    public function test_get_summary_with_profiles(): void
    {
        // First request
        $this->profiler->startProfile('request-1');
        usleep(5000);
        $this->profiler->endProfile('request-1', 200);

        // Second request
        $this->profiler->startProfile('request-2');
        usleep(10000);
        $this->profiler->endProfile('request-2', 500);

        // Third request (incomplete)
        $this->profiler->startProfile('request-3');

        $summary = $this->profiler->getSummary();

        $this->assertEquals(3, $summary['total_requests']);
        $this->assertEquals(2, $summary['completed_requests']);
        $this->assertEquals(1, $summary['failed_requests']); // 500 status
        $this->assertGreaterThan(0, $summary['total_time']);
        $this->assertGreaterThan(0, $summary['average_time']);
        $this->assertGreaterThan(0, $summary['min_time']);
        $this->assertGreaterThan(0, $summary['max_time']);
    }

    public function test_generate_request_id(): void
    {
        $requestId1 = FetchProfiler::generateRequestId('GET', 'https://example.com/api');
        $requestId2 = FetchProfiler::generateRequestId('GET', 'https://example.com/api');

        // IDs should be unique even for the same method/uri
        $this->assertNotEquals($requestId1, $requestId2);

        // ID should start with the method
        $this->assertStringStartsWith('GET_', $requestId1);
    }

    public function test_enable_returns_fluent_interface(): void
    {
        $result = $this->profiler->disable()->enable();

        $this->assertSame($this->profiler, $result);
    }

    public function test_disable_returns_fluent_interface(): void
    {
        $result = $this->profiler->enable()->disable();

        $this->assertSame($this->profiler, $result);
    }
}
