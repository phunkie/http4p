# Client

`send` sends a `Request` to another service and describes its response as an `IO<Response>`. A client has the same shape as a handler, `Request => IO<Response>`, so everything you know about responses applies: the status, the headers, and a body that is a `Stream` read from the connection chunk by chunk as it is compiled.

```php
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Response;

use function Phunkie\Http4p\Functions\client\send;
use function Phunkie\Http4p\Functions\Headers;
use function Phunkie\Http4p\Functions\Request;

$response = send(Request(Method::GET, 'http://catalogue.internal/books/1'))->unsafeRun();

$response->status->code;                                // 200
$response->headers->get('content-type');                // application/json
implode('', $response->body->compile()->toArray());     // the whole body, when it fits in memory

send(Request(Method::POST, 'http://catalogue.internal/books', Headers(['content-type' => 'application/json']), json_encode($book)));
```

The request goes out and the status and headers are read when the IO runs; the body is not read until the stream is compiled. A failed status is a response like any other: `send` does not throw on a 404 or a 500, only a `RuntimeException` when no connection could be made. The second argument is the timeout in seconds, for connecting and for each read.

## Reading a large body

The body is a stream of chunks, so it composes with every stream operation and is consumed in constant memory. `lines()` from phunkie/streams turns the chunks into lines however the chunks were cut:

```php
use function Phunkie\Effect\Functions\io\io;

send(Request(Method::GET, 'http://catalogue.internal/books/export'))
    ->flatMap(fn(Response $response) => $response->body
        ->lines()
        ->evalTap(fn(string $line) => io(fn() => fwrite($file, $line . "\n")))
        ->compile()
        ->drain())
    ->unsafeRun();
```

## Decoding an export into entities

`decodeLines` reads a body of newline-delimited JSON as a `Stream` of an entity, one per line, with the rules of [entity decoding](entity-decoding.md): every constructor parameter without a default is required, the `#[Generated]` ones included since the rows come from a store, value objects and enums are built from the scalars, and a line that does not fit fails the stream with a `DecodeFailure` when the stream reaches it.

```php
use function Phunkie\Http4p\Functions\decodeLines;

send(Request(Method::GET, 'http://catalogue.internal/books/export'))
    ->flatMap(fn(Response $response) => decodeLines($response, Book::class)
        ->evalTap(fn(Book $book) => io(fn() => $index->add($book)))
        ->compile()
        ->drain())
    ->unsafeRun();
```

Between two http4p services, the exporting side streams `all(Book::class)->stream()` as lines (see [Streaming](streaming.md)) and the consuming side reads them back as `Book`s; neither holds more than a chunk of the export at a time. The integration test in `tests/Integration/ClientTest.php` sends 200,000 rows through this pair and checks that both processes stay flat.

An arrow function captures variables by value. A closure that counts or collects by reference inside `evalTap` must therefore be created outside the `fn` that hands it over, or it writes to the arrow function's copy:

```php
$count = 0;
$counting = function (Book $book) use (&$count) {
    return io(function () use (&$count) {
        $count++;
    });
};

send($request)
    ->flatMap(fn(Response $response) => decodeLines($response, Book::class)->evalTap($counting)->compile()->drain())
    ->unsafeRun();
```
