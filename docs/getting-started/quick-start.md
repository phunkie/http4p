# Quick Start

Create an `index.php` file:

```php
<?php

use Phunkie\Http4p\Server\PhpServer;

use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\response\Ok;
use function Phunkie\Http4p\Functions\routes\GET;

require_once __DIR__ . '/vendor/autoload.php';

$routes = HttpRoutes(
    GET('/', fn() => Ok('Hello World!')),
    GET('/greet/:name', fn(string $name) => Ok(['greeting' => sprintf('Hello %s!', $name)])),
);

(new PhpServer($routes))
    ->run(8000)
    ->unsafeRun();
```

Run the server:
```bash
php index.php
```

A handler receives the path parameters by name, and the `Request` when it declares it, and returns `IO<Response>`. The response constructors, `Ok`, `Created`, `NotFound` and the rest, take the body as their first argument and encode it as JSON.

## Reading a body

`decode($req, SomeClass::class)` validates the JSON body against a class's constructor and hands the handler its fields; a body that does not fit is answered with a `400` before the handler runs:

```php
use Phunkie\Http4p\Request;

use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\response\Created;
use function Phunkie\Http4p\Functions\routes\POST;

final readonly class Greeting
{
    public function __construct(public string $name, public string $language = 'en') {}
}

POST('/greetings', fn(Request $req) =>
    decode($req, Greeting::class)->flatMap(Created(...))
);
```

See [Entity Decoding](../core/entity-decoding.md) for the rules and [Building a REST API](../examples/rest-api.md) for a complete application.
