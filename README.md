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
use Phunkie\Http4p\{Request, Response};
use function Phunkie\Http4p\HttpRoutes;
use function Phunkie\Http4p\Response\{Ok, Created};

// Define routes
$routes = HttpRoutes(
    GET('/users/:id', fn(Request $req) =>
        Ok(['id' => $req->params['id']])
    ),
    
    POST('/users', fn(Request $req) =>
        Created(['message' => 'User created'])
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
use function Phunkie\Http4p\HttpRoutes;
use function Phunkie\Http4p\Response\{Ok, Created, NoContent, NotFound};

$routes = HttpRoutes(
    GET('/api/tasks', fn(Request $req) => getAllTasks()),
    GET('/api/tasks/:id', fn(Request $req) => getTask($req->params['id'])),
    POST('/api/tasks', fn(Request $req) => createTask($req->body)),
    PUT('/api/tasks/:id', fn(Request $req) => updateTask($req->params['id'], $req->body)),
    DELETE('/api/tasks/:id', fn(Request $req) => deleteTask($req->params['id']))
);
```

### Response Helpers

Use functional response constructors (JSON is the default format):

```php
use function Phunkie\Http4p\Response\{Ok, Created, Accepted, NoContent, BadRequest, NotFound, InternalServerError};

// Success responses - body is passed as argument
Ok(['status' => 'success']);
Created(['id' => 123, 'name' => 'New User']);
Created($user);  // Pass objects directly
Accepted('Processing');
NoContent();

// Error responses
BadRequest(['error' => 'Invalid input']);
NotFound(['error' => 'Resource not found']);
InternalServerError('Something went wrong');
```

### Middleware Composition

Build middleware pipelines using functional composition:

```php
use function Phunkie\Http4p\Middleware\{compose, Logger, Auth, CORS, RateLimit};

// Compose middleware functionally
$middleware = compose(
    Logger(),
    Auth(),
    CORS(['*']),
    RateLimit(100, 60)
);

$app = $middleware($routes);

// Alternative: Use combine method
$middleware = Logger()
    ->combine(Auth())
    ->combine(CORS(['*']))
    ->combine(RateLimit(100, 60));

$app = $middleware($routes);
```

### Streaming Responses

Handle large responses efficiently:

```php
use function Phunkie\Http4p\Response\Ok;
use Phunkie\Streams\IO\File\Path;

GET('/stream', fn(Request $req) =>
    Ok(Stream(new Path('large-file.json'))
        ->map(fn($line) => json_decode($line))
    )
);
```

### Effect Integration

All HTTP operations are IO effects:

```php
use function Phunkie\Http4p\Response\{Ok, NotFound};

$program = getUserFromDb($id)
    ->flatMap(fn($user) => 
        getProfileFromApi($user->id)
            ->map(fn($profile) => ['user' => $user, 'profile' => $profile])
    )
    ->flatMap(fn($data) => Ok($data))
    ->handleError(fn($e) => NotFound(['error' => 'User not found']));

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

## API Reference

### Response Constructors

All response constructors are available as functions:

```php
// 2xx Success
Ok()              // 200
Created()         // 201
Accepted()        // 202
NoContent()       // 204

// 3xx Redirection
MovedPermanently($location)  // 301
Found($location)             // 302
SeeOther($location)          // 303

// 4xx Client Errors
BadRequest()      // 400
Unauthorized()    // 401
Forbidden()       // 403
NotFound()        // 404
MethodNotAllowed() // 405
Conflict()        // 409

// 5xx Server Errors
InternalServerError()  // 500
NotImplemented()       // 501
ServiceUnavailable()   // 503
```

### Middleware

All middleware are available as functions in the `Phunkie\Http4p\Middleware` namespace:

```php
Logger()              // Request/response logging
Auth()                // Authentication
CORS($origins)        // CORS headers
RateLimit($max, $window)  // Rate limiting
Timeout($seconds)     // Request timeout
Compression()         // Response compression
```

## Documentation

Coming soon.

## License

MIT Licence

## Acknowledgments

- Inspired by [http4s](https://http4s.org/)
- Built on [Phunkie](https://github.com/phunkie/phunkie)
