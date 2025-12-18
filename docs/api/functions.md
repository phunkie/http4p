# Functions Reference

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
