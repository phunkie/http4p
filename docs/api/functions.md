# Functions Reference

Every helper lives under `Phunkie\Http4p\Functions`. Import what you use with `use function`:

| Namespace | Functions |
|-----------|-----------|
| `Phunkie\Http4p\Functions` | `HttpRoutes`, `Request`, `Response`, `Headers`, `Status`, `StatusOk` and the other status constructors, `decode`, `FileResponse` |
| `Phunkie\Http4p\Functions\routes` | `GET`, `POST`, `PUT`, `PATCH`, `DELETE` |
| `Phunkie\Http4p\Functions\response` | `Ok`, `Created`, `Accepted`, `NoContent`, `BadRequest`, `Unauthorized`, `Forbidden`, `NotFound`, `Conflict`, `InternalServerError` |
| `Phunkie\Http4p\Functions\decoding` | `json` |
| `Phunkie\Http4p\Functions\middleware` | `Through`, `Logger`, `Cors` |
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

- `Ok(mixed $body = null, array $headers = []): Response`
- `Created(mixed $body = null, array $headers = []): Response`
- `Accepted(mixed $body = null, array $headers = []): Response`
- `NoContent(): Response`
- `BadRequest(mixed $body = null): Response`
- `Unauthorized(mixed $body = null): Response`
- `Forbidden(mixed $body = null): Response`
- `NotFound(mixed $body = null): Response`
- `InternalServerError(mixed $body = null): Response`

## Streaming
- `Stream(mixed $source): Stream`
- `FileResponse(string $path): IO<Response>`
- `StreamFromPDO(\PDOStatement $stmt): Stream`

## Core Types Helpers
- `Response(Status $status, Headers $headers, Stream $body): Response`
- `Request(Method $method, string $uri, Headers $headers, Stream $body): Request`
- `Headers(array $headers): Headers`
- `Status(int $code, string $reason): Status`
