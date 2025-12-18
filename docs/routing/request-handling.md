# Request Handling

Route handlers receive details about the current request implicitly or explicitly via `Request` object injection.

## The Request Object

```php
use Phunkie\Http4p\Request;

POST('/users', fn(Request $req) => ...);
```

Properties:
- `method`: The HTTP method (GET, POST, etc)
- `uri`: The request URI string
- `headers`: `Headers` object
- `body`: `Phunkie\Streams\Type\Stream`
- `pathParams`: Array of extracted path parameters

## Reading the Body

Since `body` is a Stream, you can process it lazily.

```php
// Convert entire body to string (in-memory) if needed:
$content = $req->body->readAll()->toString(); // Note: implementation depends on Stream helpers for full read

// Or better, map over chunks:
$req->body->map(fn($chunk) => ...);
```
