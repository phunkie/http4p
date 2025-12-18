# File Streaming

Http4p provides built-in support for streaming files efficiently.

## Serving Files

Use the global `FileResponse` helper to creates a streamed response from a file path. It automatically sets the correct `Content-Type` and `Content-Length` headers and streams the file content without loading it all into memory.

```php
use function Phunkie\Http4p\Functions\FileResponse;

$response = FileResponse('/path/to/large-video.mp4');
```

This helper wraps `Phunkie\Streams\IO\Read` to read the file in chunks (default 4KB).

## Receiving Files (Request Body)

The `Request` body is fully streamable. By default, `PhpServer` wraps `php://input` in a Stream.

```php
POST('/upload', fn(Request $req) =>
    // $req->body is a Stream connected to php://input
    // You can process it chunk by chunk
    $req->body
        ->map(fn($chunk) => processChunk($chunk))
        ->compile()
        ->drain()
        ->map(fn() => Ok('Uploaded'))
);
```

This allows handling large file uploads with constant memory usage.
