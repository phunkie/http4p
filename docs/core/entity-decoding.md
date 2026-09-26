# Entity Decoding

Entity Decoding is the process of transforming the HTTP Request body stream into a usable data structure or domain object.

Unlike [Middleware](../middleware/basics.md), which handles cross-cutting concerns, decoding is typically specific to the domain logic of a particular route.

## Using the `decode` helper

`decode($request)` reads the body and decodes it as JSON. When the body is not valid JSON the effect fails with a `DecodeFailure`, and the router answers with a `400` whose JSON body carries the message, so a handler never sees a malformed body:

```php
use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\decoding\jsonObject;

POST('/users', fn(Request $req) =>
    decode($req, jsonObject('name', 'email'))->flatMap(fn(array $data) => Created(new User($data['name'], $data['email'])))
);
```

`jsonObject(...$fields)` decodes a JSON object and keeps only the named fields, failing with a `DecodeFailure` when the body is not an object or none of the fields is present. Called without fields it keeps the whole object. `json()` is the default decoder and accepts any JSON value.

## Custom decoders

The second argument is any `callable(string): mixed`. Throw `DecodeFailure` from it to get the same `400` treatment:

```php
use Phunkie\Http4p\DecodeFailure;

$csv = fn(string $body) => '' === $body ? throw new DecodeFailure('Body is empty.') : str_getcsv($body);

POST('/import', fn(Request $req) => decode($req, $csv)->flatMap(fn(array $row) => Ok($row)));
```
