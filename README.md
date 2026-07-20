# Google Cloud Run Function framework

[![CI](https://github.com/christianjbrown/php-gcp-function-lib/actions/workflows/ci.yml/badge.svg)](https://github.com/christianjbrown/php-gcp-function-lib/actions/workflows/ci.yml)

A strongly-typed PHP framework for building [Google Cloud Run function](https://cloud.google.com/run) HTTP endpoints that return a **consistent JSON envelope**. You write the business logic; the library handles header-based authorization, CORS, CDN cache-control headers, and uniform success/error responses.

It is built around [PSR-7](https://www.php-fig.org/psr/psr-7/): you hand it a `ServerRequestInterface` and it returns a `ResponseInterface`. Configuration is read straight from your Cloud Run environment variables.

- **Uniform envelope** — every response carries `success`, `timestamp_unix`, and `timestamp_iso8601`, plus `data`, `version` (the Cloud Run revision), or `error` as appropriate.
- **Header authorization** — optionally require a header key/value before running your handler.
- **CORS + caching** — `Access-Control-*`, `Vary`, `Cache-Control`, and `Surrogate-Control` headers derived from config.
- **Safe error handling** — user-friendly exceptions surface their message; anything else returns a generic error unless `DEBUG` is on.



## :heavy_check_mark: Prerequisites

- [Git](https://git-scm.com/)
- [PHP](https://www.php.net/) 8.5 or higher (8.x)
- [Composer](https://getcomposer.org/)

:bulb: If you're on MacOS and have [Homebrew](https://brew.sh/), PHP and Composer will install with `brew install composer`.



## :building_construction: Installation

For your composer-enabled project:

```bash
composer require christianjbrown/php-gcp-function-lib
```



## :computer: Usage

Implement `DataProviderInterface` with your endpoint's logic — it receives the PSR-7 request and returns an array that becomes the response `data`:

```php
use ChristianBrown\GcpFunction\DataProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MyDataProvider implements DataProviderInterface
{
    /**
     * @return mixed[]
     */
    public function getData(ServerRequestInterface $request): array
    {
        // Your business logic. Throw a UserFriendlyExceptionInterface to return
        // a specific message to the client; any other Throwable is hidden unless DEBUG is on.
        return ['hello' => 'world'];
    }
}
```

Build a `FunctionConfig` from your Cloud Run environment variables with `FunctionConfigTransformer`, wire it into a `CloudFunction`, and run the request:

```php
use ChristianBrown\GcpFunction\CloudFunction;
use ChristianBrown\GcpFunction\FunctionConfigTransformer;

$config = (new FunctionConfigTransformer())->transform($_ENV);

$cloudFunction = new CloudFunction(new MyDataProvider(), $config);

$response = $cloudFunction->run($request); // Psr\Http\Message\ResponseInterface
```

`$response` is a PSR-7 response ready to emit (e.g. with `guzzlehttp/psr7`'s HTTP factories or your Cloud Run function's runtime).

### Environment variables

`FunctionConfigTransformer::transform()` reads these keys (only `K_REVISION` is required — Cloud Run sets it automatically):

| Variable | Purpose |
| --- | --- |
| `K_REVISION` | **Required.** The revision id, surfaced as `version` in the response. |
| `DEBUG` | `"true"` to return raw exception messages instead of a generic error. |
| `REQUIRED_HEADER_KEY` / `REQUIRED_HEADER_VALUE` | Require this header on the request, else `401`. |
| `REQUIRED_ORIGIN` | Value for `Access-Control-Allow-Origin` (enables the `Vary` header). |
| `USE_CACHE_TTL` | `s-maxage` / `max-age` seconds for successful responses. |
| `USE_CACHE_BUT_REQUEST_TTL` | `stale-while-revalidate` seconds. |
| `USE_CACHE_IF_ERROR_TTL` | `stale-if-error` seconds. |

### Response shape

A successful response:

```json
{
    "data": { "hello": "world" },
    "success": true,
    "timestamp_iso8601": "2026-07-15T12:00:00+00:00",
    "timestamp_unix": 1784030400,
    "version": "my-service-00001-abc"
}
```

An error response omits `data` and adds `error`:

```json
{
    "error": "Not authorized",
    "success": false,
    "timestamp_iso8601": "2026-07-15T12:00:00+00:00",
    "timestamp_unix": 1784030400,
    "version": "my-service-00001-abc"
}
```

## :rotating_light: Error handling

Inside your `DataProviderInterface::getData()`, throwing an exception that implements [`christianjbrown/php-user-friendly-exception-lib`](https://github.com/christianjbrown/php-user-friendly-exception-lib)'s `UserFriendlyExceptionInterface` returns its message to the client (HTTP 500). Any other `Throwable` returns a generic `"An unhandled error occurred"` message — unless `DEBUG` is enabled, in which case the raw message is returned to aid debugging. A failed authorization check short-circuits with `"Not authorized"` (HTTP 401) before your handler runs.

## :page_facing_up: License

Released under the [MIT License](LICENSE).
