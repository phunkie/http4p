# Phunkie Http4p

A functional HTTP library for PHP inspired by Scala's http4s.

## Overview

Http4p provides a purely functional approach to building HTTP servers and clients in PHP. Built on Phunkie Effect and Streams, it offers:

- **Type-safe routing** - Compile-time route validation
- **Effect-based handlers** - All HTTP operations as IO effects
- **Streaming support** - Handle large requests/responses efficiently
- **Composable middleware** - Build complex pipelines from simple parts
- **Functional error handling** - No exceptions, just values

## Installation

```bash
composer require phunkie/http4p
```

## Requirements

- PHP 8.2 or higher
- phunkie/phunkie ^1.0
- phunkie/effect ^1.0
- phunkie/streams ^1.0

## Quick Start

```php
use Phunkie\Http4p\{HttpRoutes, Response, Request};
use function Phunkie\Effect\Functions\io\io;

// Define routes
$routes = HttpRoutes::of(
    GET('/users/:id', fn(Request $req) =>
        io(fn() => Response::ok()->json(['id' => $req->params['id']]))
    ),
    
    POST('/users', fn(Request $req) =>
        io(fn() => Response::created()->json(['message' => 'User created']))
    )
);

// Run server
$server = HttpServer::create()
    ->withRoutes($routes)
    ->bindHttp(8080);

$server->run()->unsafeRun();
```

## Features

### Type-Safe Routing

Define routes with pattern matching and type safety:

```php
$routes = HttpRoutes::of(
    GET('/api/tasks', fn(Request $req) => getAllTasks()),
    GET('/api/tasks/:id', fn(Request $req) => getTask($req->params['id'])),
    POST('/api/tasks', fn(Request $req) => createTask($req->body)),
    PUT('/api/tasks/:id', fn(Request $req) => updateTask($req->params['id'], $req->body)),
    DELETE('/api/tasks/:id', fn(Request $req) => deleteTask($req->params['id']))
);
```

### Middleware Composition

Build middleware pipelines:

```php
$middleware = Middleware::compose(
    Logger::middleware(),
    Auth::middleware(),
    CORS::middleware(['*']),
    RateLimit::middleware(100, 60)
);

$app = $middleware($routes);
```

### Streaming Responses

Handle large responses efficiently:

```php
GET('/stream', fn(Request $req) =>
    io(fn() => 
        Response::ok()
            ->stream(
                Stream::fromFile('large-file.json')
                    ->map(fn($line) => json_decode($line))
            )
    )
);
```

### Effect Integration

All HTTP operations are IO effects:

```php
$program = for(
    $user <- getUserFromDb($id),
    $profile <- getProfileFromApi($user->id),
    $response <- io(fn() => Response::ok()->json([
        'user' => $user,
        'profile' => $profile
    ]))
)->yield($response);

$result = $program->unsafeRun();
```

### Client Support

Make HTTP requests functionally:

```php
$client = HttpClient::create();

$program = $client
    ->get('https://api.example.com/users/1')
    ->flatMap(fn($response) => 
        io(fn() => json_decode($response->body))
    );

$user = $program->unsafeRun();
```

## Documentation

Coming soon.

## License

MIT Licence

## Acknowledgments

- Inspired by [http4s](https://http4s.org/)
- Built on [Phunkie](https://github.com/phunkie/phunkie)
