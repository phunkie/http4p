# Entity Decoding

Entity Decoding is the process of transforming the HTTP Request body stream into a usable data structure or domain object.

Unlike [Middleware](../middleware/basics.md), which handles cross-cutting concerns, decoding is typically specific to the domain logic of a particular route.

## Using the `decode` helper

The `decode` function simplifies reading and parsing the body. By default, it decodes JSON.

```php
POST('/users', fn(Request $req) =>
    decode($req)->flatMap(function($data) {
        
        if (!$data) return BadRequest("Invalid JSON");
        
        $user = new User($data['name'], $data['email']);
        
        return Created($user);
    })
);
```

## Route Wrappers

For repetitive decoding logic, you can create higher-order functions (route wrappers).

```php
function WithJson(callable $handler): callable {
    return fn(Request $req) => 
        decode($req)->flatMap(fn($json) => $handler($json));
}

POST('/users', WithJson(fn($data) => Created($data)));
```
