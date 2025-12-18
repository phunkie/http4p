# Core Concepts

Http4p is built on three main concepts: **IO**, **Streams**, and **Immutable Data**.

## 1. IO (Input/Output)

In Http4p, your route handlers don't just "do things" (like write to the database or echo output). Instead, they return an **IO** description of what they *want* to do.

- **Why?** This makes your code predictable, easy to test, and safe.
- **How?** Wrap side effects in `io(fn() => ...)`.

```php
// Instead of:
// echo "Hello";
// return 200;

// You do:
return io(fn() => Ok("Hello"));
```

## 2. Streams

Web requests and responses often involve moving data (bytes) from one place to another. Http4p treats bodies as **Streams**.

- **Request Body**: A stream of data coming IN.
- **Response Body**: A stream of data going OUT.
- **Benefit**: You can process huge files or datasets chunk-by-chunk without crashing your server memory.

## 3. Immutable Data

Requests and Responses are **immutable**. You cannot change them "in place".
To modify a response, you create a new one based on the old one.

```php
$res = Ok("Hello");
$jsonRes = $res->withHeader('Content-Type', 'application/json');
```

This prevents bugs where one part of your code accidentally breaks another part by mutating shared objects.
