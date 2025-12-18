# Entity Decoding

**Should EntityDecoder be seen as a middleware?**
**No.**

Middleware is best suited for cross-cutting concerns (Logging, CORS, Auth) that generically wrap the `Request` -> `Response` flow.
Entity Decoding is specific to a route: it transforms the **Body** into a **Domain Object**. Since different routes expect different objects, this logic belongs inside or wrapped around the specific handler, not in the global middleware stack.

## How to Decode

The recommended approach is to decode the entity explicitly within your handler. This ensures type safety and clarity.

```php
POST('/users', fn(Request $req) =>
    // decode defaults to JSON
    decode($req)->flatMap(function($data) {
        
        if (!$data) return BadRequest("Invalid JSON");
        
        // Use constructor, not static method!
        $user = new User($data['name'], $data['email']);
        
        return Created($user);
    })
);
```

## Route Wrappers

For repetitive decoding, you can create higher-order functions (route wrappers) instead of middleware.

```php
function WithJson(callable $handler): callable {
    return fn(Request $req) => 
        $req->body->readAll()->flatMap(fn($json) => $handler(json_decode($json, true)));
}

POST('/users', WithJson(fn($data) => Created($data)));
```
