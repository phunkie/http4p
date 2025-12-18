# Entity Encoding

Entity Encoding is the process of converting your domain objects (like a `User` class or an `array`) into a `Stream` of bytes for the HTTP Response.

## Automatic Encoding
When you use helpers like `Ok($body)`, `http4p` attempts to encode the body.

- **Strings**: Used as-is.
- **Streams**: Used as-is.
- **Arrays/Objects**: By default, converted to JSON using `JsonEncoder`.

## Custom Encoders
You can provide a generic `EntityEncoder`.

```php
interface EntityEncoder {
    public function encode(mixed $entity): string|Stream;
    public function contentType(): string;
}
```
