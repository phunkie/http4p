# Quick Start

Create an `index.php` file:

```php
<?php

use Phunkie\Http4p\Server\PhpServer;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\GET;
use function Phunkie\Http4p\Functions\response\Ok;

require_once __DIR__ . '/vendor/autoload.php';

$routes = HttpRoutes(
    GET('/', fn() => Ok('Hello World!'))
);

(new PhpServer($routes))
    ->run(8000)
    ->unsafeRun();
```

Run the server:
```bash
php index.php
```
