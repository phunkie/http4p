# Custom Middleware

Writing middleware is simple. It is a function that takes the `next` handler and returns a `new` handler.

## Structure

```php
use Phunkie\Http4p\Request;
use Phunkie\Effect\IO\IO;
use function Phunkie\Http4p\Functions\response\Forbidden;

function MyMiddleware(): callable
{
    return function (callable $next): callable {
        return function (Request $req) use ($next): IO {
            
            // 1. Pre-processing (before handler)
            // e.g. check headers, auth, etc.
            if ($req->headers->get('X-Block')) {
                return Forbidden();
            }

            // 2. Call Next
            $responseIO = $next($req);

            // 3. Post-processing (after handler)
            return $responseIO->map(function($res) {
                return $res->withHeader('X-Checked', 'True');
            });
        };
    };
}
```

## Composition

Combine your middleware with `Through`.

```php
$app = Through($router, MyMiddleware(), Logger());
```
