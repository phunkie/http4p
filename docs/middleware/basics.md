# Middleware

Middleware allows you to wrap your application to intercept requests before they reach your logic, or transform responses before they are sent.

## Usage

Use the `Through` helper to apply middleware to your routes. Since `PhpServer` accepts a generic handler, you can wrap your routes with middleware before passing them to the server.

```php
use Phunkie\Http4p\Server\PhpServer;
use function Phunkie\Http4p\Functions\middleware\{Through, Logger, Cors};

// 1. Create your routes
$routes = HttpRoutes(...);

// 2. Wrap them with middleware
$app = Through(
    new Router($routes), // Wrap routes in a handler (or PhpServer handles ImmList too, but Through expects callable)
    Logger(),
    Cors()
);

// Note: Through expects the first argument to be a callable handler.
// You can pass `fn($req) => (new Router($routes))->route($req)` or simply instantiate the router.

(new PhpServer($app))->run()->unsafeRun();
```

## The Middleware Signature

Middleware is a higher-order function. It takes a "next" handler and returns a new handler.

```php
type Handler = callable(Request): IO<Response>;
type Middleware = callable(Handler): Handler;
```

This simple functional signature allows infinite composition.
