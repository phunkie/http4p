<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Phunkie\Http4p\Request;
use Phunkie\Http4p\Response;
use Phunkie\Http4p\Server\PhpServer;
use Phunkie\Streams\IO\Resource;
use Tests\Integration\Fixtures\Book;

use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\response\NotFound;
use function Phunkie\Http4p\Functions\response\Ok;
use function Phunkie\Http4p\Functions\routes\GET;
use function Phunkie\Http4p\Functions\routes\POST;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$books = fn (int $count) => new class ($count) implements Resource {
    private int $next = 1;

    public function __construct(private int $count)
    {
    }

    public function pull(int $chunkSize): mixed
    {
        if ($this->next > $this->count) {
            return Resource::EOF;
        }
        $book = new Book($this->next, "Book {$this->next}", 'Ada', 0 === $this->next % 2);
        $this->next++;

        return json_encode($book) . "\n";
    }
};

(new PhpServer(HttpRoutes(
    GET('/hello', fn () => Ok('hello')),
    GET('/missing', fn () => NotFound(['error' => 'nothing here'])),
    POST('/echo', fn (Request $req) => decode($req)->flatMap(fn ($json) => Ok($json))),
    GET('/books', fn (Request $req) => Ok(\Stream($books((int) ($req->query('count') ?? 3))))
        ->map(fn (Response $response) => $response->withHeader('content-type', 'application/x-ndjson'))),
    GET('/peak', fn () => Ok(['bytes' => memory_get_peak_usage()])),
)))->run()->unsafeRun();
