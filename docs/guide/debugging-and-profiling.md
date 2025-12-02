---
title: Debugging & Profiling
description: Capture sanitized request/response snapshots, tune log levels, and profile Fetch PHP requests.
---

# Debugging & Profiling

Fetch PHP ships with structured debug snapshots, optional performance profiling, and PSR-3 logging hooks. These tools help you understand what was sent over the wire without leaking secrets.

## Debug snapshots

Enable debug mode on a handler (or the global client) to capture the last request/response, timing, connection stats, and memory deltas.

```php
use Fetch\Support\DebugInfo;

$handler = fetch_client()->getHandler()
    ->withDebug([
        'response_body' => 2048, // bytes to keep; true for full, false to omit
        'request_body' => true,
        'request_headers' => true,
        'response_headers' => true,
        'timing' => true,
        'memory' => true,
    ]);

$response = $handler->get('https://api.example.com/users');

$debug = $handler->getLastDebugInfo();
print_r($debug?->toArray());     // Structured array
echo $debug?->dump();            // Pretty JSON string
```

Notes:

- Snapshots are collected for successful responses, cached hits (`X-Cache-Status`), and 304 revalidations.
- Async requests call the same sync execution path internally, so debug info is still populated.
- Set `withDebug(false)` to disable quickly.

## Logging

Both `Client` and `ClientHandler` accept a PSR-3 logger. Request/response traces use a configurable log level and automatically redact sensitive data (`Authorization`, `Cookie`, `auth` credentials).

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('php://stdout'));

$client = fetch_client();
$client->setLogger($logger);
$client->getHandler()->withLogLevel('info'); // default: debug

$client->get('https://api.example.com/status');
```

## Performance profiling

Attach a profiler to collect timing and memory metrics across requests. The built-in `FetchProfiler` tracks start/end times plus optional phase events (DNS, connect, SSL, transfer) recorded inside the handler.

```php
use Fetch\Support\FetchProfiler;

$profiler = new FetchProfiler();

$handler = fetch_client()->getHandler()
    ->withProfiler($profiler)
    ->withConnectionPool(); // include pool/dns stats in debug snapshots

$handler->get('https://api.example.com/users');
$handler->get('https://api.example.com/posts');

$profiles = $profiler->getAllProfiles();
$summary = $profiler->getSummary(); // totals/averages/min/max + memory deltas
```

Profiling is opt-in and low overhead when disabled. The handler automatically generates request IDs and completes profiles even when exceptions are thrown (errors are marked as incomplete/failed).

## Practical recipes

- **Track a flaky endpoint**: enable `withDebug()` on a dedicated handler, keep `response_body` small (e.g., 512 bytes), and inspect `X-Cache-Status` plus `connection` stats to see whether failures correlate with cache misses or pool exhaustion.
- **Production-safe logging**: keep `withDebug(false)` but set a PSR-3 logger and `withLogLevel('info')` to emit high-level traces without payloads.
- **One-off diagnostics**: wrap a single request with `withDebug()` and `withProfiler()` on a cloned handler (`withClonedOptions()`) so global settings remain untouched.
