# Custom Streams

You can create custom streaming sources by implementing the `Phunkie\Streams\Type\Pull` interface.

## Implementing Pull

Usually you extend a base class or implement `Pull`. The key methods are `next()`, `current()`, `valid()`.

```php
use Phunkie\Streams\Type\Pull;

class RandomNumberPull implements Pull {
    // ... Implement Iterator methods ...
}
```

## Creating the Stream

Use the `Stream()` helper to create a Stream from your custom Pull implementation.

```php
use Phunkie\Streams\Type\Stream;

$stream = Stream(new RandomNumberPull());
```

This ensures your custom source integrates seamlessly with all Stream operations (map, filter, etc.) and `http4p` Responses.
