# Response Model

The `Response` object is an immutable representation of an HTTP response.

```php
final readonly class Response
{
    public function __construct(
        public Status $status,
        public Headers $headers,
        public Stream $body
    ) {}
}
```

## Immutability

All components are immutable. `Headers` uses an immutable map. `Status` is a value object. `Stream` describes a lazy computation.

## Effectful Body

The `body` is a `Stream`, which encapsulates `IO` effects for reading/generating the body content. This separates the description of the response from the execution of sending it.
