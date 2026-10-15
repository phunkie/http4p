# Type Signatures

Http4p describes an application with a handful of types. Reading them explains most of the library.

## A handler is `Request => IO<Response>`

```php
fn(Request $request): IO   // IO<Response>
```

A handler sends nothing. It returns an `IO<Response>` from phunkie/effect: a description of the work, run by the server once the router has chosen the route. That is why a handler can be called in a test with a `Request` and inspected without a server, and why `map`, `flatMap` and `recover` on the `IO` compose the response the way they compose any other effect.

`Ok`, `Created`, `NotFound` and the other constructors return `IO<Response>` too, so a handler built on a query is one chain:

```php
GET('/users/:id', fn(int $id) => $users->get($id)->flatMap(Ok(...)));   // IO<Response>
```

## An application is `callable(Request): IO<Response>`

`Router` is that callable over an `ImmList<Route>`; `Through($app, ...$middleware)` returns another one; `PhpServer` takes one. Anything with the shape of a handler is an application, which is why the client has the same shape: `send(Request): IO<Response>`.

## Middleware is `callable(callable): callable`

A middleware takes an application and returns an application. `Recover(SomeException::class, $handler)` wraps every route once; see [Middleware](../middleware/basics.md).

## A body is a `Stream`

`Request::$body` and `Response::$body` are `Phunkie\Streams\Type\Stream`: a lazy sequence of chunks, read from `php://input` or a socket, or produced by a query, only when the stream is compiled. The server writes it chunk by chunk and a client reads it the same way. `Ok($value)` encodes the value into a one-chunk stream; `Ok($stream)` passes the stream through. See [Streaming](streaming.md).

## `decode` is `Request => IO<mixed>`

`decode($request, Author::class)` yields the entity's fields as an array, `decode($request)` the raw JSON, and a body that does not fit fails the `IO` with a `DecodeFailure`, which the router answers with a `400`. `decodeLines($message, Author::class)` is the streaming form, a `Stream` of entities. See [Entity Decoding](entity-decoding.md).

## Reading a signature

| Signature | Meaning |
|---|---|
| `IO<Response>` | a response, once the effect runs |
| `IO<Response>` failing with `DecodeFailure` | a 400, answered by the router before the handler's work |
| `Stream` | chunks, produced as they are consumed |
| `callable(Request): IO<Response>` | a handler, an application, a client |
