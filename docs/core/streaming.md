# Streaming in Http4p

In **Http4p**, streaming is not an afterthought or a special mode: it is the default.

Every `Request` body is a Stream.
Every `Response` body is a Stream.

The server pulls one chunk at a time from the body, writes it and flushes it before pulling the next, and a client reads a response the same way, so memory holds one chunk whether the payload is 5KB of JSON or a 5GB export.

## Streaming Responses

When you create a response, you are providing a description of a stream of data. `PhpServer` pulls from this stream and sends each chunk to the client as soon as it is produced: written, flushed through any output buffer and handed to the SAPI. No `Content-Length` is set for a streamed body, so the SAPI sends it with chunked transfer encoding.

### 1. Basic Output

Simple values are automatically converted to streams:

```php
GET('/', fn() => Ok("Hello World"));
```

### 2. Generating Data (Push)

You can stream data dynamically to the client. This is useful for large reports, logs, or real-time event feeds. `Stream()` is phunkie/streams' global factory, so it needs no import:

```php
GET('/numbers', fn() =>
    Ok(Stream(range(1, 10))
        ->map(fn($n) => "$n\n")
        ->evalTap(fn() => io(fn() => usleep(100000)))
    )
);
```

The client receives each number as it is produced: the server does not wait for the stream to end before sending the first byte.

### 3. Exporting rows

With [phunkie/phetch](https://github.com/phunkie/phetch) as the store, `stream()` on a query yields the rows one fetch at a time. Encode each as a line of newline-delimited JSON:

```php
GET('/books/export', fn() =>
    all(Book::class)->stream()->run($conn)
        ->flatMap(fn(Stream $books) => Ok($books->map(fn(Book $book) => json_encode($book) . "\n")))
        ->map(fn(Response $response) => $response->withHeader('content-type', 'application/x-ndjson'))
);
```

Nothing holds more than one row: it is fetched, encoded, written and flushed, and only then is the next one fetched. The [client](client.md) page shows the service that reads it back.

### 4. File Streaming

For serving files, use the `FileResponse` helper which sets up a stream from disk.
([Read more in File Streaming](../streaming/files.md))

```php
use function Phunkie\Http4p\Functions\FileResponse;
GET('/video', fn() => FileResponse('/media/movie.mp4'));
```

---

## Streaming Requests

Incoming request bodies are also Streams. This allows you to process uploads or large payloads chunk-by-chunk.

### 1. Process as you read

Instead of reading the whole body into memory, map over the stream:

```php
POST('/uppercase', fn(Request $req) =>
    Ok($req->body->map(fn($chunk) => strtoupper($chunk)))
);
```

Pipe a large file to this endpoint and it streams the uppercase version back as it reads, holding one chunk at a time.

## Deployment

`PhpServer` runs the application in whatever SAPI executes it. PHP's built-in server, `php -S`, is single-threaded: it serves one request at a time, so a long streamed response blocks every other client until it ends. Keep it for development.

For production, run behind php-fpm with nginx or Apache in front, or under FrankenPHP or RoadRunner; they serve requests concurrently, send a body without `Content-Length` as chunked transfer encoding and forward every flush. Make sure the reverse proxy does not buffer responses (`proxy_buffering off;` in nginx for the streaming routes, or the `X-Accel-Buffering: no` header), or the client sees the whole body only at the end.

## Why this matters

1.  **Time to first byte**: clients see data as soon as it is produced.
2.  **Memory**: a body of any size is processed one chunk at a time.
3.  **Backpressure**: a client that reads slowly blocks the write, which stops the pull from your source; see [Backpressure](../streaming/backpressure.md).
