# Path Parameters

A segment written `:name` in a pattern matches one path segment and is passed to the handler by position:

```php
GET('/users/:id', fn(int $id) => ...);
GET('/users/:userId/posts/:slug', fn(int $userId, string $slug) => ...);
```

The parameters arrive in the order they appear in the pattern, followed by the `Request` when the handler's last parameter is typed `Request`.

## Types

A numeric segment is passed as an `int`, any other segment as a `string`. Type the handler's parameter to match: `int $id` for `/users/7`, `string $slug` for `/posts/hello-world`. A parameter typed `int` for a segment that turns out not to be numeric raises a `TypeError`, answered as a `500`, so declare `string` where a segment may be either.

## On the request

The extracted parameters are also on the request as `$request->pathParams`, keyed by name, and `decode($request, Book::class)` reads them: a path parameter fills the constructor parameter of the same name ahead of the body, so `POST /authors/:authorId/books` needs no `authorId` in the JSON. See [Entity Decoding](../core/entity-decoding.md).

## Query strings

The query string is not part of the pattern. Read it with `$request->query('name')` for one value or `$request->queryParams()` for all of them; see [Request Handling](request-handling.md).
