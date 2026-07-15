# CLAUDE.md

Guidance for working in this repository. Match the existing conventions exactly — this codebase is
small, uniform, and highly opinionated, so new code should be indistinguishable from what's here.

## What this is

A strongly-typed PHP 8.3+ framework for building **Google Cloud Run / Cloud Functions** HTTP
endpoints that return a consistent JSON envelope, with built-in header authorization, CORS, and CDN
cache-control headers. It is built around [PSR-7](https://www.php-fig.org/psr/psr-7/): a consumer
implements `DataProviderInterface` (their business logic), builds a `FunctionConfig` from Cloud Run
environment variables via `FunctionConfigTransformer`, wires both into a `CloudFunction`, and calls
`run(ServerRequestInterface): ResponseInterface`.

## Commands

Binaries install into `bin/` (Composer `bin-dir`), not `vendor/bin/`. Both `bin/` and `vendor/` are
gitignored and Composer-installed, so run `composer install` first.

| Task | Command |
| --- | --- |
| Run tests + coverage (opens HTML report) | `composer test` |
| Run tests, no coverage | `php -d memory_limit=-1 ./bin/phpunit --no-coverage` |
| Run one test | `php -d memory_limit=-1 ./bin/phpunit --filter CloudFunctionTest` |
| Check code style | `composer check-style` |
| Auto-fix code style | `composer fix-style` |
| Check / fix style on git diff only | `composer check-style-diff` / `composer fix-style-diff` |
| Static analysis | `composer stan` |

Style tooling comes from the `christianjbrown/php-code-quality-scripts` dev dependency (php-cs-fixer
+ PHP_CodeSniffer, **Symfony2 coding standard**); the `bin/php-cs*` scripts are thin wrappers over it.
Static analysis is **PHPStan at `level: max`** (`phpstan.neon.dist`, run with `composer stan` /
`./bin/phpstan analyse`), and there is a **GitHub Actions CI workflow** (`.github/workflows/ci.yml`)
that runs style, PHPStan, and the PHPUnit suite with coverage on every push/PR to `main`. Because the
runtime and dev dependencies are private `dev-main` GitHub packages, CI injects a `COMPOSER_AUTH`
secret. Always run `composer fix-style` first (php-cs-fixer auto-fixes what it can), then
`composer check-style` to surface any remaining violations that must be fixed by hand, then
`composer stan` and `composer test` before finishing.

## Architecture

Everything lives directly under `src/` (no sub-layers). PSR-4: `ChristianBrown\CloudFunction\` →
`src/`, `ChristianBrown\CloudFunction\Tests\` → `tests/`.

- **`CloudFunction`** (`src/CloudFunction.php`) — the entry point. Constructed with a
  `DataProviderInterface` + `FunctionConfigInterface`. Its `run()` checks header authorization
  (returns a `JsonErrorResponse` 401 if it fails), calls `getData()`, and wraps the result in a
  `JsonSuccessResponse`. It catches `UserFriendlyExceptionInterface` (returns the message) and any
  other `Throwable` (returns the raw message only when `DEBUG` is on, otherwise a generic error).
- **`DataProviderInterface`** — the single method a consumer implements:
  `getData(ServerRequestInterface): array`.
- **`FunctionConfig`** / **`FunctionConfigInterface`** — a mutable settings object (fluent setters)
  holding the revision, debug flag, required header key/value, required origin, and three cache TTLs.
- **`FunctionConfigTransformer`** / **`FunctionConfigTransformerInterface`** — builds a
  `FunctionConfig` from an environment-variable array (`K_REVISION` required; the rest optional),
  with `ENV_*` key constants on the interface.
- **`AbstractJsonResponse`** + **`JsonSuccessResponse`** / **`JsonErrorResponse`** — build the
  standardized JSON body and set the CORS / `Vary` / `Cache-Control` / `Surrogate-Control` headers.
  If JSON encoding throws, they fall back to a 500 with `ERROR_JSON_ENCODING`.
  **Note:** `AbstractJsonResponse` is the one deliberate exception to the "no abstract base classes"
  convention below — it exists to share the response-building logic across the two concrete
  responses while extending Guzzle's PSR-7 `Response`.
- **`ResponseInterface`** — extends the PSR-7 response interface and centralizes every header name,
  content type, the default `HEADERS` array, and the response-body key names as typed constants.

## Conventions (follow all of these)

- `declare(strict_types=1);` on every file, immediately after `<?php`.
- **Every concrete class is `final` and implements a matching `...Interface`** in the same namespace
  (`CloudFunction`/`CloudFunctionInterface`, `FunctionConfig`/`FunctionConfigInterface`). The only
  abstract class is `AbstractJsonResponse` (see the note above); otherwise prefer composition.
- **Constants live on the interface, not the class**: env-var keys (`ENV_*`), header names/values,
  response body keys (`RESPONSE_API_KEY_*`), and error messages (`ERROR_*`) — all typed constants
  (`public const string ...`, `public const int ...`, `public const array ...`).
- **No constructor property promotion** — declare typed `private` properties and assign them in the
  constructor body. Class members (properties then methods) are ordered **alphabetically**.
- Import functions and constants explicitly with `use function sprintf;` / `use const JSON_THROW_ON_ERROR;`
  (after class imports, blank line between groups), and call them unqualified.
- **Config**: required fields are constructor args; optionals default to `null`/`false`. Getters
  `getX()`; fluent setters `setX($value)` (param literally `$value`) that `return $this` typed as
  the **interface**. No enums, no `readonly`, no immutability.
- Arrays crossing a public boundary carry a `@param mixed[]` / `@return mixed[]` docblock so PHPStan
  `level: max` is satisfied (the payload can be a list or a map, so `mixed[]`, not
  `array<string, mixed>`).

## Testing

The `phpunit.xml` config is strict (`requireCoverageMetadata`, `beStrictAboutCoverageMetadata`,
`failOnRisky`, `failOnWarning`, `beStrictAboutOutputDuringTests`, path coverage). With that in mind:

- **Coverage must stay at 100%** — line, path, method/function, and branch. Every code path,
  including each defensive guard (e.g. the `instanceof FunctionConfigInterface` guards in
  `AbstractJsonResponse`) and every optional cache-header combination, must be exercised. **Always
  run `composer test` and check the coverage report** before finishing — it prints a text summary to
  stdout and writes HTML to `.phpunit.cache/code-coverage-html/index.html`. New code without full
  coverage is not done.
- **Every test class needs a `#[CoversClass(...)]` attribute** (may list more than one) or the run
  fails. Use PHPUnit 12 **attributes, not annotations**: `#[CoversClass]`, `#[DataProvider]`,
  `#[TestWith]`.
- One `final class XTest extends TestCase` per production class, methods named `test<Scenario>`.
- Mock every collaborator with `$this->createMock(SomeInterface::class)`; assert statically
  (`self::assertSame`). Reference the **same interface constants** production code uses — for both
  data keys and expected messages — so no strings are hardcoded.

## Adding a feature

1. Add the class + its matching interface (constants, if any, on the interface).
2. Follow the conventions above (final, no promotion, alphabetical members, function imports,
   `mixed[]` docblocks on array boundaries).
3. Add a matching `#[CoversClass]` test under `tests/`.
4. Run `composer fix-style`, then `composer check-style`, then `composer stan`, then `composer test`
   and **confirm the coverage report is 100%** on lines, paths, methods, and branches.
