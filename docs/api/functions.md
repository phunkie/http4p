# Functions Reference

Every helper lives under `Phunkie\Http4p\Functions`. Import what you use with `use function`:

| Namespace | Functions |
|-----------|-----------|
| `Phunkie\Http4p\Functions` | `HttpRoutes`, `Request`, `Response`, `Headers`, `Status`, `StatusOk` and the other status constructors, `decode`, `FileResponse` |
| `Phunkie\Http4p\Functions\routes` | `GET`, `POST`, `PUT`, `PATCH`, `DELETE` |
| `Phunkie\Http4p\Functions\response` | `Ok`, `Created`, `Accepted`, `NoContent`, `BadRequest`, `Unauthorized`, `Forbidden`, `NotFound`, `Conflict`, `UnprocessableEntity`, `InternalServerError` |
| `Phunkie\Http4p\Functions\decoding` | `json` |
| `Phunkie\Http4p\Functions\middleware` | `Through`, `Logger`, `Cors`, `Recover` |
| `Phunkie\Http4p\Functions\headers` | `get`, `put`, `remove` |
| `Phunkie\Http4p\Functions\status` | `isSuccess`, `isRedirect`, `isClientError`, `isServerError` |

```php
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\routes\GET;
use function Phunkie\Http4p\Functions\response\Ok;
```

## Routing
- `HttpRoutes(Route ...$routes): ImmList<Route>`
- `GET(string $path, callable $handler): Route`
- `POST(string $path, callable $handler): Route`
- `PUT(string $path, callable $handler): Route`
- `DELETE(string $path, callable $handler): Route`
- `PATCH(string $path, callable $handler): Route`

## Response Factories
Namespace: `Phunkie\Http4p\Functions\response`

Every constructor takes the body first and an optional `EntityEncoder`, and returns `IO<Response>`; the default encoder writes JSON, see [Entity Encoding](../core/entity-encoding.md).

- `Ok(mixed $body = null, ?EntityEncoder $encoder = null)`
- `Created(mixed $body = null, ?EntityEncoder $encoder = null)`
- `Accepted(mixed $body = null, ?EntityEncoder $encoder = null)`
- `NoContent()`
- `BadRequest(mixed $body = null, ?EntityEncoder $encoder = null)`
- `Unauthorized(mixed $body = null, ?EntityEncoder $encoder = null)`
- `Forbidden(mixed $body = null, ?EntityEncoder $encoder = null)`
- `NotFound(mixed $body = null, ?EntityEncoder $encoder = null)`
- `Conflict(mixed $body = null, ?EntityEncoder $encoder = null)`
- `UnprocessableEntity(mixed $body = null, ?EntityEncoder $encoder = null)`
- `InternalServerError(mixed $body = null, ?EntityEncoder $encoder = null)`

## Streaming
- `FileResponse(string $path): IO<Response>`

The stream factories come from phunkie/streams and are global functions, imported by nothing:

- `Stream(mixed ...$source): Stream`
- `StreamFromPDO(PDOStatement $stmt): Stream`

## Core Types Helpers
- `Response(Status $status, Headers $headers, Stream $body): Response`
- `Request(Method $method, string $uri, Headers $headers, Stream $body): Request`
- `Headers(array $headers): Headers`
- `Status(int $code, string $reason): Status`
