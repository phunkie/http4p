<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\Request;
use Phunkie\Http4p\Response;
use Phunkie\Http4p\Server\Output;
use Phunkie\Http4p\Server\PhpServer;
use Phunkie\Streams\IO\Resource;
use RuntimeException;

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\response\Ok;
use function Phunkie\Http4p\Functions\routes\GET;

final class PhpServerTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/7?page=2';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testHandleRequestBuildsTheRequestFromServerGlobalsAndRoutesIt(): void
    {
        $server = new PhpServer(HttpRoutes(
            GET('/users/:id', fn (int $id, Request $request) => Ok(['id' => $id, 'accept' => $request->headers->get('accept')])),
        ));

        $response = $server->handleRequest()->unsafeRun();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->status->code);
        $this->assertSame('{"id":7,"accept":"application/json"}', implode('', $response->body->toArray()));
    }

    public function testHandleRequestTurnsAThrowingHandlerIntoA500Response(): void
    {
        $server = new PhpServer(fn (Request $request) => io(fn () => throw new RuntimeException('boom')));

        $response = $server->handleRequest()->unsafeRun();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->status->code);
        $this->assertSame('application/json', $response->headers->get('content-type'));
        $this->assertStringContainsString('boom', implode('', $response->body->toArray()));
    }

    public function testSendResponseWritesTheStatusTheHeadersAndTheBody(): void
    {
        $output = $this->recordingOutput();
        $server = new PhpServer(HttpRoutes(), $output);

        $server->sendResponse(Ok('hello')->unsafeRun())->unsafeRun();

        $this->assertSame(['status 200', 'header content-type: application/json', 'write hello'], $output->log);
    }

    public function testSendResponseWritesEachChunkBeforePullingTheNext(): void
    {
        $output = $this->recordingOutput();
        $source = new class ($output->log) implements Resource {
            private array $chunks = ['one', 'two'];

            public function __construct(private array &$log)
            {
            }

            public function pull(int $chunkSize): mixed
            {
                $chunk = array_shift($this->chunks);
                $this->log[] = null === $chunk ? 'pull end' : "pull $chunk";

                return $chunk ?? Resource::EOF;
            }
        };
        $server = new PhpServer(HttpRoutes(), $output);

        $server->sendResponse(Ok(\Stream($source))->unsafeRun())->unsafeRun();

        $this->assertSame(['status 200', 'pull one', 'write one', 'pull two', 'write two', 'pull end'], $output->log);
    }

    public function testSendResponseAddsNoContentLengthToAStreamedBody(): void
    {
        $output = $this->recordingOutput();
        $server = new PhpServer(HttpRoutes(), $output);

        $server->sendResponse(Ok(\Stream('a', 'b'))->unsafeRun())->unsafeRun();

        $this->assertSame(['status 200', 'write a', 'write b'], $output->log);
    }

    private function recordingOutput(): Output
    {
        return new class () implements Output {
            public array $log = [];

            public function status(int $code): void
            {
                $this->log[] = "status $code";
            }

            public function header(string $name, string $value): void
            {
                $this->log[] = "header $name: $value";
            }

            public function write(string $chunk): void
            {
                $this->log[] = "write $chunk";
            }
        };
    }
}
