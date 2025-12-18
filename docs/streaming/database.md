# Database Streaming

You can stream database query results efficiently using `phunkie/streams` integration with PDO.

## StreamFromPDO

The `StreamFromPDO` helper creates a Stream from a `PDOStatement`. It pulls rows one by one using `fetch(PDO::FETCH_ASSOC)`.

```php
use function StreamFromPDO;

$stmt = $pdo->query('SELECT * FROM huge_table');
$stream = StreamFromPDO($stmt);

// Use in Response
GET('/export', fn() =>
    Ok($stream->map(fn($row) => json_encode($row) . "\n"))
);
```

## PDOPull

Under the hood, this uses `Phunkie\Streams\Pull\PDOPull`. You can use this class directly if you need to build custom streams or combine it with other Pull mechanics.

```php
use Phunkie\Streams\Pull\PDOPull;
use Phunkie\Streams\Type\Stream;

$pull = new PDOPull($stmt);
$stream = Stream::fromPull($pull);
```
