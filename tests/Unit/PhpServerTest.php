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
use Phunkie\Http4p\Server\PhpServer;
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

    public function testSendResponseWritesTheBody(): void
    {
        $server = new PhpServer(HttpRoutes());

        ob_start();
        $server->sendResponse(Ok('hello')->unsafeRun())->unsafeRun();
        $output = ob_get_clean();

        $this->assertSame('hello', $output);
    }
}
