# Phunkie Http4p

A functional HTTP library for PHP inspired by Scala's http4s.

## Overview

Http4p provides a purely functional approach to building HTTP servers and clients in PHP. Built on Phunkie Effect and Streams, it offers:

- **Type-safe routing** - Compile-time route validation
- **Effect-based handlers** - All HTTP operations as IO effects
- **Streaming bodies** - Responses use `Stream<F, Byte>` for constant memory usage
- **Composable middleware** - Build complex pipelines from simple parts
- **Functional error handling** - No exceptions, just values

## Installation

```bash
composer require phunkie/http4p
```

## Requirements

- PHP 8.2 || 8.3 || 8.4
- phunkie/phunkie ^1.0
- phunkie/effect ^1.2
- phunkie/streams ^1.0

## Core Concepts

### Response Model

Http4p uses `Response<F>` where the body is a `Stream<F, Byte>`:

```php
class Response<F> {
    public Status $status;
    public Headers $headers;
    public Stream<F, Byte> $body;  // Streaming body
}
```

This design enables:
- **Constant memory** - Stream large responses without loading into memory
- **Backpressure** - Handle slow clients gracefully
- **Cancellation** - Stop processing when client disconnects
- **Composable effects** - Body production can perform IO operations

### Type Signatures

Route handlers return `IO<Response<IO>>`:

```php
// Handler signature
fn(int $id): IO<Response<IO>>

// Response constructors
Ok<A>(A $value): IO<Response<IO>>           // Encodes value to Stream<IO, Byte>
Ok(Stream<IO, Byte> $stream): IO<Response<IO>>  // Uses stream directly
```

## Quick Start

```php
use Phunkie\Http4p\Request;
use function Phunkie\Http4p\Functions\{HttpRoutes, PhpBuiltInServerBuilder};
use function Phunkie\Http4p\Response\{Ok, Created};

// Define routes - handlers return IO<Response<IO>>
$routes = HttpRoutes(
    GET('/users/:id', fn(int $id) =>
        Ok(['id' => $id])  // EntityEncoder converts to Stream<IO, Byte>
    ),
    
    POST('/users', fn(Request $req) =>
        Created(['message' => 'User created', 'data' => $req->body])
    )
);

// Build and run server
(new PhpServer($routes))->run(8080)->unsafeRun();
```

## Documentation

Full documentation is available in [docs/](docs/index.md).

- [Getting Started](docs/getting-started/quick-start.md)
- [Core Concepts](docs/getting-started/core-concepts.md)
- [Streaming](docs/core/streaming.md)
- [Middleware](docs/middleware/basics.md)
- [API Reference](docs/api/functions.md)

## License

MIT Licence

## Acknowledgments

- Inspired by [http4s](https://http4s.org/)
- Built on [Phunkie](https://github.com/phunkie/phunkie)
