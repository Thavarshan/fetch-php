---
title: File Handling Example
description: Submit an upload as multipart data and safely manage its stream.
---

# File Handling

Use Guzzle's `multipart` option and a readable stream for file contents. Close resources in a `finally` block. Do not manually set the multipart boundary header.

See [File Uploads](/guide/file-uploads) for a multipart example. The example requires a real application endpoint and is illustrative rather than part of the offline runnable fixture suite.
