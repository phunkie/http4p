# Types

Everything lives under `Phunkie\Http4p`. Every value is immutable: the `with` methods return a new instance.

| Type | What it is |
|---|---|
| `Request` | `method`, `uri`, `headers`, `body` (a `Stream`) and `pathParams`; `query($name)` and `queryParams()` read the query string; `withPathParams`, `withBody`, `withHeader` |
| `Response` | `status`, `headers` and `body` (a `Stream`); `withStatus`, `withHeaders`, `withHeader`, `withBody` |
| `Headers` | an immutable map of header names to values: `get`, `put`, `remove`, `toArray`. Built with `Headers([...])`; http4p itself uses lowercase names |
| `Status` | `code` and `reason`; built with `Status($code, $reason)` or `StatusOk()` and the other constructors |
| `Method` | enum: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS` |
| `Route` | a method, a pattern and a handler; built with `GET(...)`, `POST(...)` and the rest |
| `Router` | an application over an `ImmList<Route>`: `callable(Request): IO<Response>` |
| `DecodeFailure` | the exception a body that does not fit raises; `errors()` gives one message per field; the router answers it with `400` |
| `EntityEncoder` | `encode(mixed)` returning a string or a `Stream`, and `contentType()`; `Encoder\JsonEncoder` is the default |
| `Decoder\EntityDecoder` | what `decode($request, Entity::class)` uses; `EntityDecoder::stored($class)` is the form `decodeLines` uses, generated fields included |
| `Server\PhpServer` | serves an application through the SAPI; takes an optional `Server\Output` |
| `Server\Output`, `Server\SapiOutput` | where a response is written: the status, the headers, then each chunk of the body, flushed as it is written |
| `Client\ResponseHead` | the status and headers of a response read by `send`, before any of its body |
| `Client\SocketTransport` | what `send` runs: one HTTP/1.1 connection per request, plain or TLS, the request body framed by `Content-Length` or chunked as the stream allows |
| `Client\ResponseBody` | the response body as a `Resource`, decoded chunk by chunk as it is pulled, chunked, counted or until the server closes |

The functions that build and use them are listed in [Functions](functions.md).
