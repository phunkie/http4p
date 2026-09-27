# Entity Encoding

Entity Encoding is the process of converting your domain objects (like a `User` class or an `array`) into a `Stream` of bytes for the HTTP Response.

## Automatic Encoding

When you use helpers like `Ok($body)`, `http4p` encodes the body:

- **Strings**: used as they are.
- **Streams**: used as they are.
- **Anything else**: converted to JSON by `JsonEncoder`, with a `content-type` of `application/json`.

## What JsonEncoder renders

The encoder walks the value and normalises it before `json_encode` sees it, however deep:

| Value | Renders as |
|---|---|
| a `JsonSerializable` | whatever `jsonSerialize()` returns; phunkie's `ImmList`, `ImmMap`, `ImmSet` and tuples are, from phunkie 1.5 |
| a `DateTimeInterface` | ISO 8601, `2020-01-02T03:04:05+00:00` |
| a backed enum | its value |
| a value object | its single value |
| any other object | an object of its public properties |

A value object is an object whose constructor takes exactly one parameter typed `int`, `float`, `string` or `bool`, and which exposes exactly one public property: `Email`, `Isbn`, `Rating`. It renders as that property, so the API sends `"ada@example.com"` where the model holds `new Email('ada@example.com')`, the inverse of what [decoding](entity-decoding.md) builds from the body. An object wrapping a date, an enum or another object is not a value object by this rule and renders as an object. An entity whose only column is a scalar matches the rule; implement `JsonSerializable` on it to render it as an object.

```php
final readonly class Author
{
    public function __construct(public int $id, public string $name, public Email $email) {}
}

Ok(new Author(1, 'Ada', new Email('ada@example.com')));   // {"id":1,"name":"Ada","email":"ada@example.com"}
```

## Custom Encoders

Pass an `EntityEncoder` as the second argument of any response constructor, `Ok($body, new CsvEncoder())`:

```php
interface EntityEncoder {
    public function encode(mixed $entity): string|Stream;
    public function contentType(): string;
}
```
