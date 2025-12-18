# Response Construction

Handlers must return an `IO<Response>`. Use the helper functions for common responses.

## Helpers

```php
use function Phunkie\Http4p\Functions\response\{Ok, Created, NotFound, BadRequest};

Ok("Hello"); // 200 OK
Created($user); // 201 Created
NotFound(); // 404 Not Found
```

## Custom Status and Headers

```php
use function Phunkie\Http4p\Functions\Response;
use function Phunkie\Http4p\Functions\Status;
use function Phunkie\Http4p\Functions\Headers;

Response(
    Status(202, "Accepted"),
    Headers(['X-Custom' => 'Value']),
    Stream("Processing...")
);
```

## Body

The body argument in helpers (`Ok($body)`) is automatically converted to a `Stream`.
- Strings are converted to `Stream($string)`.
- If you pass a `Stream` object, it is used directly.
