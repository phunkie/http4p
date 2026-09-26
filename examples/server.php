<?php

/*
 * Example HTTP server using Http4p
 */

require_once __DIR__ . '/../vendor/autoload.php';

use function Phunkie\Http4p\Functions\response\Created;
use function Phunkie\Http4p\Functions\routes\DELETE;
use function Phunkie\Http4p\Functions\routes\GET;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\response\NoContent;
use function Phunkie\Http4p\Functions\response\Ok;
use function Phunkie\Http4p\Functions\routes\POST;
use function Phunkie\Http4p\Functions\routes\PUT;

// Define routes
$routes = HttpRoutes(
    GET('/', fn() => Ok(['message' => 'Welcome to Http4p!'])),
    GET('/users', fn() => Ok([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ])),
    GET('/users/:id', fn(int $id) => Ok(['id' => $id, 'name' => "User $id"])),
    POST('/users', fn($req) => Created([
        'id' => 3,
        'name' => $req->body['name'] ?? 'Unknown',
    ])),
    PUT('/users/:id', fn(int $id, $req) => Ok([
        'id' => $id,
        'name' => $req->body['name'] ?? "User $id",
        'updated' => true,
    ])),
    DELETE('/users/:id', fn(int $id) => NoContent())
);

// Create and run server
$server = new \Phunkie\Http4p\Server\PhpServer($routes);
$server->run()->unsafeRun();
