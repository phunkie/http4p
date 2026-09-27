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
- Values the route already knows fill the parameter of the same name ahead of the body and are never required from it: the request's path parameters, so `POST /authors/:authorId/books` fills `$authorId`, and anything passed as the third argument, `decode($req, Book::class, ['ownerId' => $user->id])`.
- On `POST` and `PUT` every parameter without a default that is not nullable is required. A `PATCH` may carry any subset, as long as one field is known.
- Scalars must match the declared types as JSON provides them: no coercion, so `"1843"` is not an `int`. Nested entities are accepted as JSON objects.
- A parameter typed with a backed enum is built with `tryFrom`, one typed with a date class from the string, and one typed with any other class from the scalar through its constructor. An `InvalidArgumentException` thrown by that constructor becomes the field's error, so a value object such as `Isbn` or `Email` carries its own validation:

```php
final readonly class Isbn
{
    public function __construct(public string $value)
    {
        if (!preg_match('/^\d{13}$/', $value)) {
            throw new InvalidArgumentException('must be 13 digits');
        }
    }
}
```

- A parameter typed `array` whose constructor docblock says `@param list<TagName> $tags` (or `array<int>`, or `string[]`) must be a JSON list, and every element is checked or built like a scalar parameter would be; errors are keyed `tags.1`.

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

## Responding with what was decoded

The response constructors take the body as their first argument, so a handler can pass them as first-class callables instead of writing a closure:

```php
decode($req, Author::class)
    ->flatMap(fn(array $data) => create(Author::class, $data)->run($conn))
    ->flatMap(Created(...));
```

## Decoding a stream of lines

`decodeLines($message, Author::class)` reads a request or response body of newline-delimited JSON as a `Stream` of `Author`, one per line, with the same rules and errors, and two differences that follow from the lines describing stored entities: every parameter without a default is required, as on `PUT`, and the `#[Generated]` parameters are expected too. Each line becomes an instance, not an array of fields, and a line that does not fit fails the stream with the `DecodeFailure` when the stream reaches it. See [Client](client.md) for reading another service's export this way.
