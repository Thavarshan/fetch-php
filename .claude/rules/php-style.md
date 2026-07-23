---
paths:
  - "src/**/*.php"
---

# PHP source rules (`src/Fetch/**`)

- New files start with `declare(strict_types=1);`.
- Duster owns formatting (`pint.json`): PSR-12, 4-space indent, single quotes,
  imports ordered `const`, `class`, `function`. Run `composer fix` before
  committing; `composer lint` must pass.
- Keep PHPStan level 6 clean (`composer analyse`). Fix types properly — do not
  add `ignoreErrors` in `phpstan.neon` to hide a real problem.
- One class per file; PSR-4 under `Fetch\`; filename matches class.
- Route option handling through `Support\RequestOptions` / `RequestContext` and
  defaults through `Support\Defaults` / `RetryDefaults` — don't re-derive them.
- Any change to a public class/interface/signature/helper/enum/exception or a
  default is backward-compatibility-sensitive → use `fetch-php-public-api-review`.
- Match the surrounding file's style and comment density.
