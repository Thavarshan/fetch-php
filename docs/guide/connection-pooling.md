---
title: HTTP/2 and Legacy Pool APIs
description: HTTP/2 configuration and the limits of legacy pool utilities.
---

# HTTP/2 and Legacy Pool APIs

FetchPHP's custom connection-pool and DNS-cache integration is deprecated
since 4.1.0. `withConnectionPool()`, its pool diagnostics, and the handler DNS
cache methods remain available for source compatibility, but they do not
change request execution. FetchPHP does not borrow a pooled transport client
or resolve request hosts through `DnsCache`.

The `Connection` objects hold Guzzle client instances, not established TCP or
TLS sockets. They cannot guarantee socket reuse, HTTP/2 multiplexing, or
connection limits. A `DnsCache` instance remains usable as a standalone
utility; its results do not affect outgoing requests.

Reuse of HTTP connections depends on the Guzzle handler and transport. Reusing
an injected Guzzle client and its handler allows that transport to manage any
supported keep-alive behavior, but FetchPHP does not guarantee a socket will be
reused. Do not replace request hostnames with cached IP addresses: doing so can
change TLS hostname verification, SNI, proxy behavior, and routing.

## HTTP/2

`withHttp2()` can add Guzzle `version` and cURL options when HTTP/2 is enabled
and the cURL constants are available. It does not guarantee that a server
negotiates HTTP/2. Advanced stream and window configuration values are stored as
configuration and are not all translated into transport options. Guzzle's
handler, cURL build, and server determine actual protocol support.

```php
$handler = fetch_client()
    ->getHandler()
    ->withHttp2(['enabled' => true]);
```
