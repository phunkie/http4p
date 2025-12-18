# Phunkie Http4p

A functional HTTP library for PHP inspired by Scala's http4s.

## Overview

Http4p provides a purely functional approach to building HTTP servers and clients in PHP. Built on Phunkie Effect and Streams, it offers:

- **Type-safe routing** - Compile-time route validation
- **Effect-based handlers** - All HTTP operations as IO effects
- **Streaming bodies** - Responses use `Stream<F, Byte>` for constant memory usage
- **Composable middleware** - Build complex pipelines from simple parts
- **Functional error handling** - No exceptions, just values

## Installation

```bash
composer require phunkie/http4p
```

## Requirements

- PHP 8.2 || 8.3 || 8.4
- phunkie/phunkie ^1.0
- phunkie/effect ^1.2
- phunkie/streams ^1.0

## Core Concepts

### Response Model

Http4p uses `Response<F>` where the body is a `Stream<F, Byte>`:

```php
class Response<F> {
    public Status $status;
    public Headers $headers;
    public Stream<F, Byte> $body;  // Streaming body
}
```

This design enables:
- **Constant memory** - Stream large responses without loading into memory
- **Backpressure** - Handle slow clients gracefully
- **Cancellation** - Stop processing when client disconnects
- **Composable effects** - Body production can perform IO operations

### Type Signatures

Route handlers return `IO<Response<IO>>`:

```php
// Handler signature
fn(int $id): IO<Response<IO>>

// Response constructors
Ok<A>(A $value): IO<Response<IO>>           // Encodes value to Stream<IO, Byte>
Ok(Stream<IO, Byte> $stream): IO<Response<IO>>  // Uses stream directly
```

## Quick Start

```php
use Phunkie\Http4p\Request;
use function Phunkie\Http4p\Functions\{HttpRoutes, PhpBuiltInServerBuilder};
use function Phunkie\Http4p\Response\{Ok, Created};

// Define routes - handlers return IO<Response<IO>>
$routes = HttpRoutes(
    GET('/users/:id', fn(int $id) =>
        Ok(['id' => $id])  // EntityEncoder converts to Stream<IO, Byte>
    ),
    
    POST('/users', fn(Request $req) =>
        Created(['message' => 'User created', 'data' => $req->body])
    )
);

// Build and run server
$server = PhpBuiltInServerBuilder()
    ->withRoutes($routes)
    ->withPort(8080)
    ->build();

$server->run()->unsafeRun();
```

## Features

### Type-Safe Routing

Define routes with pattern matching and type safety. Route parameters are automatically extracted and passed as typed arguments to your handlers:

```php
use Phunkie\Http4p\Request;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Response\{Ok, Created, NoContent, NotFound};

$routes = HttpRoutes(
    GET('/api/tasks', fn() => getAllTasks()),
    GET('/api/tasks/:id', fn(int $id) => getTask($id)),
    POST('/api/tasks', fn(Request $req) => createTask($req->body)),
    PUT('/api/tasks/:id', fn(int $id, Request $req) => updateTask($id, $req->body)),
    DELETE('/api/tasks/:id', fn(int $id) => deleteTask($id))
);
```

### Response Helpers

Use functional response constructors (JSON is the default format):

```php
use function Phunkie\Http4p\Response\{Ok, Created, Accepted, NoContent, BadRequest, NotFound, InternalServerError};

// Success responses - body is encoded to Stream<IO, Byte>
Ok(['status' => 'success']);              // IO<Response<IO>>
Created(['id' => 123, 'name' => 'New User']);
Created($user);  // Pass objects directly
Accepted('Processing');
NoContent();

// Error responses
BadRequest(['error' => 'Invalid input']);
NotFound(['error' => 'Resource not found']);
InternalServerError('Something went wrong');
```

### Streaming Responses

Stream large responses with constant memory usage:

```php
use function Phunkie\Http4p\Response\Ok;
use function Phunkie\Streams\Stream;

// Stream a large file
GET('/download/:file', fn(string $file) =>
    Ok(Stream::fromFile("/data/{$file}.json"))  // Stream<IO, Byte>
);

// Stream database results
GET('/users/export', fn() =>
    Ok(
        where(User::class, 'active', true)
            ->stream()                          // Stream<IO, User>
            ->map(fn($u) => json_encode($u))    // Stream<IO, String>
            ->intersperse("\n")                 // Add newlines
            ->through(utf8Encode)               // Stream<IO, Byte>
    )
);

// Process and stream with backpressure
GET('/process/:file', fn(string $file) =>
    Ok(
        Stream::fromFile("/input/{$file}.csv")
            ->through(parseCsv)
            ->evalMap(fn($row) => processRow($row))  // IO effect per row
            ->map(fn($result) => json_encode($result))
            ->through(utf8Encode)
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

Http4p provides multiple ways to make HTTP requests, from explicit control to convenient helpers.

#### Explicit Request (Full Control)

Build requests explicitly for maximum control:

```php
use function Phunkie\Http4p\{HttpClient, Request};
use function Phunkie\Http4p\Method\GET;

$client = HttpClient();
$request = Request(method: GET, uri: 'http://api.example.com/users/1');

$program = $client->run($request)
    ->map(fn($response) => json_decode($response->body));

$user = $program->unsafeRun();
```

#### Fluent Builder (Convenient)

Use the fluent API for cleaner code:

```php
use function Phunkie\Http4p\HttpClient;

// With base URI
$client = HttpClient(baseUri: 'http://api.example.com');

$program = $client
    ->get('/users/1')
    ->map(fn($response) => json_decode($response->body));

$user = $program->unsafeRun();

// Without base URI
$client = HttpClient();

$program = $client
    ->get('http://api.example.com/users/1')
    ->map(fn($response) => json_decode($response->body));
```

#### Quick Helpers (Simple Cases)

For one-off requests, use the helper functions:

```php
use function Phunkie\Http4p\Client\{get, post, put, delete, patch};

// GET request
$user = get('http://api.example.com/users/1')
    ->map(fn($response) => json_decode($response->body))
    ->unsafeRun();

// POST request
$newUser = post('http://api.example.com/users', ['name' => 'John', 'email' => 'john@example.com'])
    ->map(fn($response) => json_decode($response->body))
    ->unsafeRun();

// PUT request
$updated = put('http://api.example.com/users/1', ['name' => 'John Doe'])
    ->map(fn($response) => json_decode($response->body))
    ->unsafeRun();

// DELETE request
$result = delete('http://api.example.com/users/1')
    ->flatMap(fn($response) => 
        $response->status === 204 
            ? io(fn() => true) 
            : io(fn() => false)
    )
    ->unsafeRun();
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
