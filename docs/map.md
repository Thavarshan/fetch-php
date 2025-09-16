---
title: Documentation Structure
description: Overview of the documentation structure and file organization for the Fetch PHP project
---

# Documentation Structure

docs/
├── .vitepress/
│   ├── config.js            # VitePress configuration
│   └── theme/               # Custom theme (if needed)
├── public/
│   ├── logo.png             # Your logo
│   └── favicon.ico          # Favicon
├── index.md                 # Homepage
├── guide/
│   ├── index.md             # Overview content from fetch-http-package-overview.md
│   ├── installation.md      # Installation instructions
│   ├── quickstart.md        # Quickstart guide from fetch-quickstart.md
│   ├── making-requests.md   # Basic request usage from fetch-http-client-usage-guide.md
│   ├── helper-functions.md  # Helper functions guide from helper-functions-guide.md
│   ├── working-with-responses.md  # From working-with-response-objects.md
│   ├── authentication.md    # Authentication section from usage guides
│   ├── error-handling.md    # Error handling patterns
│   ├── logging.md           # Logging configuration
│   ├── async-requests.md    # Async usage from fetch-http-client-usage-guide.md
│   ├── promise-operations.md # Promise operations from client-handler docs
│   ├── retry-handling.md    # Retry configuration
│   ├── file-uploads.md      # File upload examples
│   ├── custom-clients.md    # Custom client configuration
│   └── testing.md           # Testing with mock responses
├── api/
│   ├── index.md             # API overview
│   ├── fetch.md             # fetch() function reference
│   ├── fetch-client.md      # fetch_client() function reference
│   ├── http-method-helpers.md # get(), post(), etc. helpers
│   ├── client.md            # Client class API reference
│   ├── client-handler.md    # ClientHandler API reference
│   ├── request.md           # Request API reference
│   ├── response.md          # Response API reference
│   ├── method-enum.md       # Method enum documentation
│   ├── content-type-enum.md # ContentType enum documentation
│   └── status-enum.md       # Status enum documentation
└── examples/
    ├── index.md             # Basic examples
    ├── api-integration.md   # API integration examples
    ├── async-patterns.md    # Advanced async examples
    ├── error-handling.md    # Error handling examples
    ├── file-handling.md     # File upload/download examples
    └── authentication.md    # Authentication examples
