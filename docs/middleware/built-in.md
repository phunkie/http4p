# Built-in Middleware

Http4p comes with essential middleware to get you started.

## Logger

Use the `Logger` middleware to log requests and response statuses to the PHP error log.

```php
use function Phunkie\Http4p\Functions\middleware\Logger;

$app = Through($handler, Logger());
```

It outputs logs in the format: `[Date] METHOD /uri`.

## Cors

Use the `Cors` middleware to handle Cross-Origin Resource Sharing. It handles `OPTIONS` requests automatically and adds headers to responses.

```php
use function Phunkie\Http4p\Functions\middleware\Cors;

$app = Through($handler, Cors([
    'origin' => 'https://example.com',
    'methods' => 'GET, POST'
]));
```

### Options

| Option    | Default | Description |
|-----------|---------|-------------|
| `origin`  | `*`    | Allowed Origin |
| `methods` | `GET, POST, ...` | Allowed Methods |
| `headers` | `Content-Type...` | Allowed Headers |
| `max-age` | `86400` | Max Age for Preflight |

## Recover

`Recover(SomeException::class, $handler)` answers one class of exception, thrown anywhere inside the wrapped handler, with the response the handler returns. Anything else keeps propagating, so the built-in server still answers it with a 500. Stack one per exception class:

```php
use Phunkie\Phetch\Constraint;
use Phunkie\Phetch\ConstraintViolation;
use Phunkie\Phetch\RowNotFound;

use function Phunkie\Http4p\Functions\middleware\{Recover, Through};
use function Phunkie\Http4p\Functions\response\{Conflict, NotFound, UnprocessableEntity};

$app = Through(
    new Router($routes),
    Recover(RowNotFound::class, fn(RowNotFound $e) => NotFound(['error' => $e->getMessage()])),
    Recover(ConstraintViolation::class, fn(ConstraintViolation $e) => match ($e->constraint) {
        Constraint::Unique => Conflict(['error' => $e->getMessage()]),
        default => UnprocessableEntity(['error' => $e->getMessage()]),
    }),
);
```

The handler receives the exception, so one `Recover` can answer differently by what it carries: here phetch's `ConstraintViolation` becomes a `409` for a duplicate key and a `422` for a missing foreign row, a null or a failed check. A single route can still override it with `IO::recover()` on its own effect.
