---
title: Documentation Structure
description: Current Fetch PHP documentation sections and their purpose.
---

# Documentation Structure

The VitePress source is organized by reader task:

```text
docs/
├── index.md                 # Documentation homepage
├── guide/                   # Installation, usage, behavior, and testing
├── api/                     # Public functions, classes, enums, contracts
├── examples/                # Scenario guides and runnable snippets
├── .vitepress/config.mts    # Navigation, sidebar, and site metadata
└── public/                  # Site assets
```

Runnable PHP snippets are under `docs/examples/snippets/`. `composer docs:examples` syntax-checks PHP fences across the README and docs and executes those snippet files offline. `npm run check-links` validates local Markdown paths and fragments; `npm run build` builds the site.

The source-level class map is maintained in [`CODE_MAP.md`](https://github.com/Thavarshan/fetch-php/blob/main/CODE_MAP.md). The code is authoritative where an older description differs.
