# Streaming in Http4p

Http4p uses `phunkie/streams` to provide efficient, lazy, and memory-safe streaming for both Requests and Responses.

## The Stream Type

The core type is `Phunkie\Streams\Type\Stream`. A Stream represents a sequence of values that are computed (pulled) on demand.

- **Request Body**: `Request->body` is always a `Stream`. For small bodies, it's a stream of one value. For large inputs (file uploads), it streams from `php://input`.
- **Response Body**: `Response->body` is generally a `Stream`. This allows sending large datasets or files without loading them entirely into memory.

## Creating Streams

Use the global `Stream()` helper or specific factory functions:

```php
// Simple stream
$stream = Stream("Hello");

// Stream from array
$stream = Stream([1, 2, 3]);

// Stream from resource (e.g. file)
use Phunkie\Streams\IO\Read;
$stream = Stream(new Read('/path/to/file'));
```

## Consuming Streams

On the server side, streams are consumed efficiently (chunk by chunk) to send data to the client. You rarely need to consume them manually unless you are writing middleware or custom handlers.
