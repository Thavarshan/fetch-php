# Reddit technical discussion draft

Suggested title: **Building a JavaScript-inspired fetch() API for PHP — looking
for feedback on the tradeoffs**

I've been working on [Fetch PHP](https://github.com/Thavarshan/fetch-php) , a
Guzzle-powered HTTP client for PHP 8.3+. The design goal is a familiar
`fetch()` entry point while retaining PHP/Guzzle request options, explicit
status handling and PSR interoperability.

A synchronous request looks like:

```php
$response = fetch('https://jsonplaceholder.typicode.com/users/1');
if (!$response->successful()) {
    throw new RuntimeException('HTTP status '.$response->status());
}
$user = $response->json();
```

It does **not** implement browser Fetch semantics or make a synchronous request
nonblocking by wrapping it in a promise. I also realize many PHP applications
are perfectly served by Guzzle, Symfony HttpClient or Laravel's HTTP facade.

I'm interested in feedback on the ergonomics and honest limitations, especially
from people who maintain PHP API integrations. Would a Fetch-style entry point
help you, or does it add another abstraction without enough value?

Full implementation/docs: https://github.com/Thavarshan/fetch-php

*Disclosure: I'm the maintainer.*

---

**Publisher note:** Check the community's current self-promotion rules and ask
the moderators if uncertain. Do not post when prohibited. Post only after the
Composer distribution gate is cleared. Do not request upvotes/stars, post a
referral link or brigade.
