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
use Phunkie\Http4p\Client\ResponseHead;
use RuntimeException;

final class ResponseHeadTest extends TestCase
{
    public function testReadsTheStatusAndTheHeadersWithLowercaseNames(): void
    {
        $head = ResponseHead::fromWrapperData(['HTTP/1.1 404 Not Found', 'Content-Type: application/json', 'X-Request-Id:  abc ']);

        $this->assertSame(404, $head->status->code);
        $this->assertSame('Not Found', $head->status->reason);
        $this->assertSame(['content-type' => 'application/json', 'x-request-id' => 'abc'], $head->headers->toArray());
    }

    public function testKeepsTheLastResponseAfterRedirects(): void
    {
        $head = ResponseHead::fromWrapperData(['HTTP/1.1 302 Found', 'Location: /there', 'HTTP/1.1 200 OK', 'Content-Length: 2']);

        $this->assertSame(200, $head->status->code);
        $this->assertSame(['content-length' => '2'], $head->headers->toArray());
    }

    public function testAcceptsAStatusLineWithoutAReason(): void
    {
        $head = ResponseHead::fromWrapperData(['HTTP/1.1 204']);

        $this->assertSame(204, $head->status->code);
        $this->assertSame('', $head->status->reason);
    }

    public function testRefusesAResponseWithoutAStatusLine(): void
    {
        $this->expectException(RuntimeException::class);

        ResponseHead::fromWrapperData(['Content-Type: text/plain']);
    }
}
