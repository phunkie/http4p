# Database Streaming

You can stream database query results efficiently using `phunkie/streams` integration with PDO.

## StreamFromPDO

The `StreamFromPDO` helper, a global function from phunkie/streams, creates a Stream from a `PDOStatement`. It pulls rows one by one using `fetch(PDO::FETCH_ASSOC)`.

```php
$stmt = $pdo->query('SELECT * FROM huge_table');
$stream = StreamFromPDO($stmt);

// Use in Response
GET('/export', fn() =>
    Ok($stream->map(fn($row) => json_encode($row) . "\n"))
);
```

With [phunkie/phetch](https://github.com/phunkie/phetch) as the store, `where(User::class, 'active', true)->stream()` yields the same kind of stream with the rows hydrated into your models.

## PDOPull

Under the hood, this uses `Phunkie\Streams\Pull\PDOPull`. You can use this class directly if you need to build custom streams or combine it with other Pull mechanics; `Stream()` is global as well.

```php
use Phunkie\Streams\Pull\PDOPull;

$pull = new PDOPull($stmt);
$stream = Stream($pull);
```
