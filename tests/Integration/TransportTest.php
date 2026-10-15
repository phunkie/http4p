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
use Phunkie\Streams\IO\Resource;
use RuntimeException;

use function Phunkie\Http4p\Functions\client\send;
use function Phunkie\Http4p\Functions\Headers;
use function Phunkie\Http4p\Functions\Request;

final class TransportTest extends TestCase
{
    /** @var resource */
    private static $server;

    private static string $base;

    public static function setUpBeforeClass(): void
    {
        $port = self::freePort();
        self::$base = "http://127.0.0.1:$port";
        self::$server = proc_open(
            [PHP_BINARY, '-d', 'xdebug.mode=off', __DIR__ . '/Fixtures/rawserver.php', (string) $port],
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

    public function testSendsAStringBodyWithItsContentLength(): void
    {
        $seen = $this->inspect(Request(Method::POST, self::$base . '/inspect?x=1', Headers(['content-type' => 'text/plain']), 'hello there'));

        $this->assertSame('POST', $seen['method']);
        $this->assertSame('/inspect?x=1', $seen['target']);
        $this->assertSame('11', $seen['headers']['content-length']);
        $this->assertSame('text/plain', $seen['headers']['content-type']);
        $this->assertArrayNotHasKey('transfer-encoding', $seen['headers']);
        $this->assertSame(11, $seen['bodyLength']);
    }

    public function testStreamsAResourceBodyOutChunkedAsItIsPulled(): void
    {
        $pulled = 0;
        $source = new class ($pulled) implements Resource {
            private int $left = 300;

            public function __construct(private int &$pulled)
            {
            }

            public function pull(int $chunkSize): mixed
            {
                if (0 === $this->left--) {
                    return Resource::EOF;
                }
                $this->pulled++;

                return str_repeat('x', 1000);
            }
        };

        $seen = $this->inspect(Request(Method::PUT, self::$base . '/upload', null, \Stream($source)));

        $this->assertSame('chunked', $seen['headers']['transfer-encoding']);
        $this->assertArrayNotHasKey('content-length', $seen['headers']);
        $this->assertSame(300000, $seen['bodyLength']);
        $this->assertSame(md5(str_repeat('x', 300000)), $seen['bodyHash']);
        $this->assertSame(300, $pulled);
    }

    public function testSendsNoBodyHeadersForAGetWithoutABody(): void
    {
        $seen = $this->inspect(Request(Method::GET, self::$base . '/inspect'));

        $this->assertArrayNotHasKey('content-length', $seen['headers']);
        $this->assertArrayNotHasKey('transfer-encoding', $seen['headers']);
        $this->assertSame('close', $seen['headers']['connection']);
    }

    public function testDecodesAChunkedResponseBody(): void
    {
        $response = send(Request(Method::GET, self::$base . '/chunked'))->unsafeRun();

        $this->assertSame('chunked', $response->headers->get('transfer-encoding'));
        $this->assertSame('first second third', $this->text($response));
    }

    public function testReadsABodyUntilTheServerCloses(): void
    {
        $response = send(Request(Method::GET, self::$base . '/until-close'))->unsafeRun();

        $this->assertSame('all of it', $this->text($response));
    }

    public function testKeepsTheStatusAndReadsNothingAfterA204(): void
    {
        $response = send(Request(Method::GET, self::$base . '/empty'))->unsafeRun();

        $this->assertSame(204, $response->status->code);
        $this->assertSame('', $this->text($response));
    }

    public function testHandsBackAFailedStatusWithItsBody(): void
    {
        $response = send(Request(Method::GET, self::$base . '/missing'))->unsafeRun();

        $this->assertSame(404, $response->status->code);
        $this->assertSame('not found', $this->text($response));
    }

    public function testRefusesARelativeUri(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        send(Request(Method::GET, '/relative'))->unsafeRun();
    }

    public function testFailsWhenNothingListens(): void
    {
        $this->expectException(RuntimeException::class);

        send(Request(Method::GET, 'http://127.0.0.1:' . self::freePort() . '/'), 1.0)->unsafeRun();
    }

    private function inspect(\Phunkie\Http4p\Request $request): array
    {
        return json_decode($this->text(send($request)->unsafeRun()), true, flags: JSON_THROW_ON_ERROR);
    }

    private function text(Response $response): string
    {
        return implode('', $response->body->compile()->toArray());
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
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

        throw new RuntimeException(sprintf('The raw server did not come up on port %d.', $port));
    }
}
