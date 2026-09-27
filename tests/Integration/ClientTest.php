<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Response;
use RuntimeException;
use Tests\Integration\Fixtures\Book;

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Http4p\Functions\client\send;
use function Phunkie\Http4p\Functions\decodeLines;
use function Phunkie\Http4p\Functions\Headers;
use function Phunkie\Http4p\Functions\Request;

final class ClientTest extends TestCase
{
    /** @var resource */
    private static $server;

    private static string $base;

    public static function setUpBeforeClass(): void
    {
        $port = self::freePort();
        self::$base = "http://127.0.0.1:$port";
        self::$server = proc_open(
            [PHP_BINARY, '-d', 'xdebug.mode=off', '-S', "127.0.0.1:$port", __DIR__ . '/Fixtures/server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        self::waitForPort($port);
    }

    public static function tearDownAfterClass(): void
    {
        proc_terminate(self::$server);
        proc_close(self::$server);
    }

    public function testSendReadsTheStatusTheHeadersAndTheBody(): void
    {
        $response = send(Request(Method::GET, self::$base . '/hello'))->unsafeRun();

        $this->assertSame(200, $response->status->code);
        $this->assertSame('OK', $response->status->reason);
        $this->assertSame('application/json', $response->headers->get('content-type'));
        $this->assertSame('hello', implode('', $response->body->compile()->toArray()));
    }

    public function testSendKeepsTheStatusOfAFailedResponse(): void
    {
        $response = send(Request(Method::GET, self::$base . '/missing'))->unsafeRun();

        $this->assertSame(404, $response->status->code);
        $this->assertSame('{"error":"nothing here"}', implode('', $response->body->compile()->toArray()));
    }

    public function testSendCarriesTheRequestBody(): void
    {
        $request = Request(Method::POST, self::$base . '/echo', Headers(['content-type' => 'application/json']), '{"answer":42}');

        $response = send($request)->unsafeRun();

        $this->assertSame('{"answer":42}', implode('', $response->body->compile()->toArray()));
    }

    public function testDecodeLinesTurnsAnExportIntoEntities(): void
    {
        $books = send(Request(Method::GET, self::$base . '/books?count=3'))
            ->map(fn (Response $response) => decodeLines($response, Book::class)->compile()->toArray())
            ->unsafeRun();

        $this->assertEquals([new Book(1, 'Book 1', 'Ada', false), new Book(2, 'Book 2', 'Ada', true), new Book(3, 'Book 3', 'Ada', false)], $books);
    }

    public function testAnExportOfTwoHundredThousandRowsStaysFlatOnBothSides(): void
    {
        $count = 0;
        $counting = function (Book $book) use (&$count) {
            return io(function () use (&$count) {
                $count++;
            });
        };
        $before = memory_get_peak_usage();

        send(Request(Method::GET, self::$base . '/books?count=200000'))
            ->flatMap(fn (Response $response) => decodeLines($response, Book::class)->evalTap($counting)->compile()->drain())
            ->unsafeRun();

        $this->assertSame(200000, $count);
        $this->assertLessThan(8 * 1024 * 1024, memory_get_peak_usage() - $before, 'the client held more than a few chunks');
        $this->assertLessThan(32 * 1024 * 1024, $this->serverPeakMemory(), 'the server held the whole export');
    }

    private function serverPeakMemory(): int
    {
        $body = implode('', send(Request(Method::GET, self::$base . '/peak'))->unsafeRun()->body->compile()->toArray());

        return json_decode($body, true, flags: JSON_THROW_ON_ERROR)['bytes'];
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(stream_socket_get_name($socket, false), strrpos(stream_socket_get_name($socket, false), ':') + 1);
        fclose($socket);

        return $port;
    }

    private static function waitForPort(int $port): void
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2);
            if (false !== $connection) {
                fclose($connection);

                return;
            }
            usleep(100000);
        }

        throw new RuntimeException(sprintf('The fixture server did not come up on port %d.', $port));
    }
}
