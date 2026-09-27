# Streaming in Http4p

In **Http4p**, streaming is not an afterthought or a special mode: it is the default.

Every `Request` body is a Stream.
Every `Response` body is a Stream.

This architecture ensures that your application uses constant memory, regardless of whether you are serving a 5KB JSON payload or a 5GB video file.

## Streaming Responses

When you create a response, you are providing a description of a stream of data. The server pulls from this stream and sends chunks to the client as they become available.

### 1. Basic Output
Simple values are automatically converted to streams:
```php
GET('/', fn() => Ok("Hello World")); // Streams: "Hello", " ", "World" (conceptually)
```

### 2. Generating Data (Push)
You can stream data dynamically to the client. This is useful for large reports, logs, or real-time event feeds. `Stream()` is phunkie/streams' global factory, so it needs no import:

```php
GET('/numbers', fn() =>
    // Create a stream that yields numbers 1 to 10
    Ok(Stream(range(1, 10))
        ->map(fn($n) => "$n\n") // Format as lines
        ->evalTap(fn() => io(fn() => usleep(100000))) // Simulate work/delay
    )
);
```
In this example, the client receives each number as it is processed. The server does not wait for the loop to finish before sending the first byte.

### 3. File Streaming
For serving files, use the `FileResponse` helper which sets up an optimized stream from disk.
([Read more in File Streaming](../streaming/files.md))

```php
use function Phunkie\Http4p\Functions\FileResponse;
GET('/video', fn() => FileResponse('/media/movie.mp4'));
```

---

## Streaming Requests

Incoming request bodies are also Streams. This allows you to process uploads or large payloads chunk-by-chunk.

### 1. Process as you read
Instead of reading the whole body into memory with `$req->body->readAll()`, you can map over the stream.

```php
POST('/uppercase', fn(Request $req) =>
    // Create a response that echoes the request body, transformed to uppercase
    Ok($req->body->map(fn($chunk) => strtoupper($chunk)))
);
```
If you pipe a large file to this endpoint, it will stream the uppercase version back immediately, using negligible memory.

## Why this matters

1.  **Time to First Byte (TTFB)**: Clients see data immediately.
2.  **Memory Safety**: You never load 100MB of data into RAM to process it.
3.  **Backpressure**: If the client is slow, `http4p` stops pulling from your source stream automatically.
