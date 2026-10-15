# Route Definition

A route is a method, a path pattern and a handler:

```php
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\routes\{DELETE, GET, PATCH, POST, PUT};

$routes = HttpRoutes(
    GET('/books', fn() => $catalogue->all()->flatMap(Ok(...))),
    GET('/books/:id', fn(int $id) => $catalogue->get($id)->flatMap(Ok(...))),
    POST('/books', fn(Request $req) => decode($req, Book::class)->flatMap($catalogue->add(...))->flatMap(Created(...))),
    PUT('/books/:id', fn(int $id, Request $req) => decode($req, Book::class)->flatMap(fn(array $data) => $catalogue->replace($id, $data))->flatMap(Ok(...))),
    PATCH('/books/:id', fn(int $id, Request $req) => decode($req, Book::class)->flatMap(fn(array $data) => $catalogue->change($id, $data))->flatMap(Ok(...))),
    DELETE('/books/:id', fn(int $id) => $catalogue->remove($id)->flatMap(fn() => NoContent())),
);
```

`HttpRoutes` returns an `ImmList<Route>`, and `new Router($routes)` turns it into an application: `callable(Request): IO<Response>`.

## Matching

The router tries the routes in the order given and takes the first whose method and path both match, so put `/books/export` before `/books/:id` when both exist. The query string is not part of the match. When nothing matches the router answers `404` with `{"error": "Not Found", "path": "..."}`.

## The handler

A handler receives the path parameters in the order they appear in the pattern, followed by the `Request` when its last parameter is typed `Request`. It returns `IO<Response>`. See [Path Parameters](path-parameters.md) for how the segments are typed and [Response Construction](response-construction.md) for the constructors.

```php
GET('/authors/:authorId/books/:id', fn(int $authorId, int $id, Request $req) => ...);
```

## Errors inside a handler

An `IO` that fails with a `DecodeFailure` is answered with `400` by the router. Any other exception propagates to the middleware, where `Recover` can answer it, or to `PhpServer`, which answers `500`.

## A routes file

The convention for an application is a `routes.php` that receives its dependencies and returns the application, wrapped in middleware with `Through`:

```php
return fn(Connection $conn): callable => Through(
    new Router(HttpRoutes(
        GET('/books/:id', fn(int $id) => findOrFail(Book::class, $id)->run($conn)->flatMap(Ok(...))),
    )),
    Recover(RowNotFound::class, fn(RowNotFound $e) => NotFound(['error' => $e->getMessage()])),
);
```

`index.php` includes it and hands the result to `PhpServer`; a test calls the same callable with a `Request`. See [Building a REST API](../examples/rest-api.md).
