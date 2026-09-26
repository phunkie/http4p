# Entity Decoding

Entity Decoding is the process of transforming the HTTP Request body stream into a usable data structure or domain object.

Unlike [Middleware](../middleware/basics.md), which handles cross-cutting concerns, decoding is typically specific to the domain logic of a particular route.

## Decoding into an entity

`decode($request, Author::class)` validates the JSON body against the entity's constructor and yields its fields, keyed by constructor parameter name, ready for the persistence layer:

```php
use function Phunkie\Http4p\Functions\decode;

POST('/authors', fn(Request $req) =>
    decode($req, Author::class)
        ->flatMap(fn(array $data) => create(Author::class, $data)->run($conn))
        ->flatMap(fn(Author $author) => Created($author))
);
```

- A key matches a parameter by its exact name or by its snake_case form, so `published_year` fills `$publishedYear`.
- Parameters marked `#[Generated]` (the attribute from phunkie/phetch, read by name so http4p does not depend on it) are never expected and are dropped if sent; so are unknown keys.
- On `POST` and `PUT` every parameter without a default that is not nullable is required. A `PATCH` may carry any subset, as long as one field is known.
- Values must match the declared types as JSON provides them: no coercion, so `"1843"` is not an `int`. Nested entities are accepted as JSON objects.

A body that does not fit fails with a `DecodeFailure` carrying one message per field, and the router answers with a `400`:

```json
{"error": "Body does not describe Author.", "errors": {"email": "missing", "publishedYear": "expected int, got string"}}
```

## Raw JSON and custom decoders

`decode($request)` alone gives the raw decoded JSON. Any `callable(string): mixed` works as a decoder; throw `DecodeFailure` from it to get the same `400` treatment:

```php
use Phunkie\Http4p\DecodeFailure;

$csv = fn(string $body) => '' === $body ? throw new DecodeFailure('Body is empty.') : str_getcsv($body);

POST('/import', fn(Request $req) => decode($req, $csv)->flatMap(fn(array $row) => Ok($row)));
```
