<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use Fetch\Support\DebugInfo;
use Fetch\Support\FetchProfiler;
use PHPUnit\Framework\TestCase;

class ManagesDebugAndProfilingTest extends TestCase
{
    private ClientHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ClientHandler;
    }

    public function test_with_debug_enables_debug_mode(): void
    {
        $result = $this->handler->withDebug();

        $this->assertSame($this->handler, $result);
        $this->assertTrue($this->handler->isDebugEnabled());
    }

    public function test_with_debug_false_disables_debug_mode(): void
    {
        $this->handler->withDebug(true);
        $this->assertTrue($this->handler->isDebugEnabled());

        $this->handler->withDebug(false);
        $this->assertFalse($this->handler->isDebugEnabled());
    }

    public function test_with_debug_accepts_options_array(): void
    {
        $options = [
            'request_headers' => true,
            'request_body' => false,
            'response_body' => 512,
        ];

        $this->handler->withDebug($options);

        $this->assertTrue($this->handler->isDebugEnabled());
        $debugOptions = $this->handler->getDebugOptions();

        $this->assertFalse($debugOptions['request_body']);
        $this->assertEquals(512, $debugOptions['response_body']);
    }

    public function test_debug_mode_disabled_by_default(): void
    {
        $this->assertFalse($this->handler->isDebugEnabled());
    }

    public function test_get_debug_options_returns_defaults_when_enabled(): void
    {
        $this->handler->withDebug(true);

        $options = $this->handler->getDebugOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('request_headers', $options);
        $this->assertArrayHasKey('response_headers', $options);
        $this->assertArrayHasKey('timing', $options);
        $this->assertArrayHasKey('memory', $options);
    }

    public function test_with_profiler_sets_profiler(): void
    {
        $profiler = new FetchProfiler;

        $result = $this->handler->withProfiler($profiler);

        $this->assertSame($this->handler, $result);
        $this->assertSame($profiler, $this->handler->getProfiler());
    }

    public function test_get_profiler_returns_null_when_not_set(): void
    {
        $this->assertNull($this->handler->getProfiler());
    }

    public function test_get_last_debug_info_returns_null_initially(): void
    {
        $this->assertNull($this->handler->getLastDebugInfo());
    }

    public function test_with_debug_is_fluent(): void
    {
        $result = $this->handler
            ->withDebug(['request_headers' => true])
            ->withProfiler(new FetchProfiler);

        $this->assertInstanceOf(ClientHandler::class, $result);
    }

    public function test_debug_options_merge_with_defaults(): void
    {
        $this->handler->withDebug(['custom_option' => 'value']);

        $options = $this->handler->getDebugOptions();

        // Default options should still be present
        $this->assertArrayHasKey('request_headers', $options);
        $this->assertArrayHasKey('custom_option', $options);
        $this->assertEquals('value', $options['custom_option']);
    }
}
