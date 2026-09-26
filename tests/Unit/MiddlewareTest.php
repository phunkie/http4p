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
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Request;
use Phunkie\Http4p\Response;

use function Phunkie\Http4p\Functions\middleware\Cors;
use function Phunkie\Http4p\Functions\middleware\Logger;
use function Phunkie\Http4p\Functions\middleware\Through;
use function Phunkie\Http4p\Functions\response\Created;
use function Phunkie\Http4p\Functions\response\Ok;
use function Phunkie\Http4p\Functions\Request;

final class MiddlewareTest extends TestCase
{
    public function testCorsAnswersPreflightWithoutCallingTheHandler(): void
    {
        $handler = fn (Request $request) => Ok('handled');
        $app = Through($handler, Cors(['origin' => 'https://example.com']));

        $response = $app(Request(Method::OPTIONS, '/users'))->unsafeRun();

        $this->assertSame(200, $response->status->code);
        $this->assertSame('https://example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('86400', $response->headers->get('Access-Control-Max-Age'));
        $this->assertSame('', implode('', $response->body->toArray()));
    }

    public function testCorsDecoratesTheHandlerResponse(): void
    {
        $app = Through(fn (Request $request) => Ok('handled'), Cors());

        $response = $app(Request(Method::GET, '/users'))->unsafeRun();

        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame(['handled'], $response->body->toArray());
    }

    public function testLoggerPassesTheResponseThrough(): void
    {
        $app = Through(fn (Request $request) => Created('made'), Logger());

        $response = $app(Request(Method::POST, '/users'))->unsafeRun();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(201, $response->status->code);
    }
}
