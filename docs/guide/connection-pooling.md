---
title: Connection Pooling and HTTP/2
description:
    Current connection-pool and HTTP/2 configuration behavior in Fetch PHP.
---

# Connection Pooling and HTTP/2

`ClientHandler` exposes connection-pool, DNS-cache, and HTTP/2 configuration
APIs. The current request execution path does not borrow clients or connections
from `ConnectionPool`, and the DNS cache is not used to resolve request hosts.
These options therefore do not provide connection reuse or DNS caching for Fetch
requests today. Do not rely on them for transport behavior.

The standalone classes under `Fetch\Pool` can be used directly, but they are not
wired into Fetch's request transport. See
[issue #128](https://github.com/Thavarshan/fetch-php/issues/128) for the
integration work.

## HTTP/2

`withHttp2()` can add Guzzle `version` and cURL options when HTTP/2 is enabled
and the cURL constants are available. It does not guarantee that a server
negotiates HTTP/2. Advanced stream and window configuration values are stored as
configuration and are not all translated into transport options. Guzzle's
handler and the installed cURL build determine actual protocol support.

```php
$handler = fetch_client()
    ->getHandler()
    ->withHttp2(['enabled' => true]);
```

Do not rely on HTTP/2 multiplexing or Fetch-managed connection reuse without
verifying the actual transport and server.
