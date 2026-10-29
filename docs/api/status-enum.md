---
title: Status
description: HTTP status code enum and classification helpers.
---

# `Fetch\Enum\Status`

`Status` is an integer-backed enum for the package's named HTTP statuses. Use `Status::tryFrom($code)` when the code may not have a declared case. Classification helpers include informational, success, redirection, client-error, and server-error checks; see the enum for the exact supported cases and helper names.

Response APIs accept either an integer or a `Status` in `isStatus()`. `statusEnum()` returns null for an unrepresented status.
