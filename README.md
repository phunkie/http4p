# Phunkie Http4p

A functional HTTP library for PHP inspired by Scala's http4s.

## Overview

Http4p provides a purely functional approach to building HTTP servers and clients in PHP. Built on Phunkie Effect and Streams, it offers:

- **Routes as values** - `GET`, `POST` and friends build a list of routes, the router matches the path
- **Effect-based handlers** - Every handler returns `IO<Response>`, nothing runs until the server runs it
- **Bodies decoded against your classes** - `decode($req, User::class)` validates the JSON body against the constructor and answers a bad body with a 400 listing every problem
- **Streaming bodies** - Responses use `Stream` for constant memory usage
- **Composable middleware** - `Through($app, Logger(), Cors(), Recover(...))`
- **Errors answered where you decide** - `Recover(SomeException::class, $handler)` turns an exception into a response for the whole app, `IO::recover()` for one route

## Installation

```bash
composer require phunkie/http4p
```

## Requirements

- PHP 8.2 || 8.3 || 8.4 || 8.5
- phunkie/phunkie ^1.5
- phunkie/effect ^1.4
- phunkie/streams ^1.2

## Core Concepts

### Response Model

Http4p uses `Response` where the body is a `Stream`:

```php
class Response {
    public Status $status;
    public Headers $headers;
    public Stream $body;  // Stream
}
```

This design enables:
- **Constant memory** - Stream large responses without loading into memory
- **Backpressure** - Handle slow clients gracefully
- **Cancellation** - Stop processing when client disconnects
- **Composable effects** - Body production can perform IO operations

### Type Signatures

Route handlers return `IO`:

```php
// Handler signature
// fn(int $id): IO<Response>
fn(int $id): IO;

// Response constructors return IO<Response>
Ok($value);        // EntityEncoder turns the value into a JSON body
Ok($stream);       // Uses the stream as the body
```

The constructors take the body as their first argument, so they compose as first-class callables: `$io->flatMap(Ok(...))`.

## Quick Start

```php
<?php

use Phunkie\Http4p\Request;
use Phunkie\Http4p\Router;
use Phunkie\Http4p\Server\PhpServer;

use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\middleware\{Recover, Through};
use function Phunkie\Http4p\Functions\response\{Created, NotFound, Ok};
use function Phunkie\Http4p\Functions\routes\{GET, POST};

require_once __DIR__ . '/vendor/autoload.php';

final readonly class User
{
    public function __construct(public string $name, public string $email) {}
}

$users = new Users();   // whatever holds your data; its methods return IO

$app = Through(
    new Router(HttpRoutes(
        GET('/users', fn() => $users->all()->flatMap(Ok(...))),

        GET('/users/:id', fn(int $id) => $users->get($id)->flatMap(Ok(...))),

        POST('/users', fn(Request $req) =>
            decode($req, User::class)
                ->flatMap(fn(array $data) => $users->add($data))
                ->flatMap(Created(...))
        ),
    )),
    Recover(UserNotFound::class, fn(UserNotFound $e) => NotFound(['error' => $e->getMessage()])),
);

(new PhpServer($app))->run(8080)->unsafeRun();
```

A `POST /users` with `{"name": 7}` never reaches the handler; the router answers:

```json
{"error": "Body does not describe User.", "errors": {"name": "expected string, got int", "email": "missing"}}
```

## Calling another service

`send` is the client: a `Request` in, an `IO<Response>` out, its body a stream read from the connection as it is compiled. A newline-delimited JSON export decodes straight into entities, one line at a time:

```php
use function Phunkie\Http4p\Functions\client\send;
use function Phunkie\Http4p\Functions\decodeLines;

send(Request(Method::GET, 'http://catalogue.internal/books/export'))
    ->flatMap(fn(Response $response) => decodeLines($response, Book::class)
        ->evalTap(fn(Book $book) => io(fn() => $index->add($book)))
        ->compile()
        ->drain())
    ->unsafeRun();
```

The server writes and flushes each chunk before producing the next, and the client reads the same way, so neither side holds more than a chunk of the export. See [Client](docs/core/client.md) and [Streaming](docs/core/streaming.md).

## Documentation

Full documentation is available in [docs/](docs/index.md).

- [Getting Started](docs/getting-started/quick-start.md)
- [Core Concepts](docs/getting-started/core-concepts.md)
- [Entity Decoding](docs/core/entity-decoding.md)
- [Streaming](docs/core/streaming.md)
- [Client](docs/core/client.md)
- [Middleware](docs/middleware/basics.md)
- [REST API example](docs/examples/rest-api.md)
- [API Reference](docs/api/functions.md)

## License

MIT Licence

## Acknowledgments

- Inspired by [http4s](https://http4s.org/)
- Built on [Phunkie](https://github.com/phunkie/phunkie)
