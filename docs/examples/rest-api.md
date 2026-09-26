# Building a REST API

A complete user API: routes that decode their input against the entity, answer with the response constructors, and let one middleware turn the domain's exceptions into status codes.

```php
<?php

use Phunkie\Http4p\Request;
use Phunkie\Http4p\Router;
use Phunkie\Http4p\Server\PhpServer;

use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\middleware\{Recover, Through};
use function Phunkie\Http4p\Functions\response\{Conflict, Created, NoContent, NotFound, Ok};
use function Phunkie\Http4p\Functions\routes\{DELETE, GET, PATCH, POST};

require_once __DIR__ . '/vendor/autoload.php';

final readonly class User
{
    public function __construct(
        #[Generated] public int $id,
        public string $name,
        public Email $email,
    ) {
    }
}

$users = new Users();   // any store whose methods return IO; it throws UserNotFound and EmailTaken

$app = Through(
    new Router(HttpRoutes(
        GET('/users', fn() => $users->all()->flatMap(Ok(...))),

        GET('/users/:id', fn(int $id) => $users->get($id)->flatMap(Ok(...))),

        POST('/users', fn(Request $req) =>
            decode($req, User::class)
                ->flatMap(fn(array $data) => $users->add($data))
                ->flatMap(Created(...))
        ),

        PATCH('/users/:id', fn(int $id, Request $req) =>
            decode($req, User::class)
                ->flatMap(fn(array $data) => $users->change($id, $data))
                ->flatMap(Ok(...))
        ),

        DELETE('/users/:id', fn(int $id) => $users->remove($id)->flatMap(fn() => NoContent())),
    )),
    Recover(UserNotFound::class, fn(UserNotFound $e) => NotFound(['error' => $e->getMessage()])),
    Recover(EmailTaken::class, fn(EmailTaken $e) => Conflict(['error' => $e->getMessage()])),
);

(new PhpServer($app))->run(8000)->unsafeRun();
```

What each piece does:

- `decode($req, User::class)` reads the JSON body against `User`'s constructor. `id` is marked `#[Generated]` (the attribute from phunkie/phetch, read by name), so it is never expected. `Email` is a value object whose constructor throws `InvalidArgumentException` on a bad address, and that message becomes the field's error. On `POST` every other parameter is required; on `PATCH` any subset is accepted.
- A body that does not fit is answered with `400` and one message per field, before the handler runs.
- `->flatMap(Ok(...))` and `->flatMap(Created(...))` pass the effect's value to the response constructor; no closure needed.
- `Recover(UserNotFound::class, ...)` and `Recover(EmailTaken::class, ...)` answer those exceptions with `404` and `409` wherever they are thrown. Anything else is still a `500` from the server.

With [phunkie/phetch](https://github.com/phunkie/phetch) as the store, `$users->get($id)` is `findOrFail(User::class, $id)->run($conn)` and the exceptions are phetch's `RowNotFound` and `ConstraintViolation`; its README shows the same API on a database.
