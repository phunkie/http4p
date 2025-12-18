# Building a REST API

Here is a complete example of a simple User API.

```php
use Phunkie\Http4p\Server\PhpServer;
use Phunkie\Http4p\Request;
use function Phunkie\Http4p\Functions\{HttpRoutes, GET, POST};
use function Phunkie\Http4p\Functions\response\{Ok, Created, NotFound};
use function Phunkie\Effect\Functions\io\io;

// 1. Define Routes
$routes = HttpRoutes(
    // List Users
    GET('/users', fn() =>
        // Simulate DB fetch
        Ok(['alice', 'bob'])
    ),

    // Create User
    POST('/users', fn(Request $req) =>
        // Read body JSON
        $req->body->readAll()->map(function($json) {
            $user = json_decode($json, true);
            return Created($user);
        })
    ),

    // Get User By ID
    GET('/users/:id', fn($id) =>
        $id === '1' ? Ok(['name' => 'Alice']) : NotFound(['error' => 'User not found'])
    )
);

// 2. Run Server
(new PhpServer($routes))
    ->run(8000)
    ->unsafeRun();
```

This API:
- Lists users
- Creates a user (reading request body)
- Gets a user by ID (using path parameters)
