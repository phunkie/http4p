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
