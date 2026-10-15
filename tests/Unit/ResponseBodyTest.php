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
use Phunkie\Http4p\Client\ResponseBody;
use Phunkie\Streams\IO\Resource;

final class ResponseBodyTest extends TestCase
{
    public function testDecodesChunkedTransferEncodingHoweverSmallThePulls(): void
    {
        $body = ResponseBody::chunked($this->socketWith("5\r\nhello\r\n7;ext=1\r\n, world\r\n0\r\nTrailer: x\r\n\r\n"));

        $this->assertSame('hello, world', $this->drain($body, 3));
    }

    public function testCountsAContentLengthDownAndStopsThere(): void
    {
        $body = ResponseBody::ofLength($this->socketWith('abcdefXXX'), 6);

        $this->assertSame('abcdef', $this->drain($body, 4));
    }

    public function testReadsUntilTheServerCloses(): void
    {
        $this->assertSame('whole body', $this->drain(ResponseBody::untilClose($this->socketWith('whole body')), 4));
    }

    public function testHasNothingToPullAfterAHeadOrA204(): void
    {
        $body = ResponseBody::none($this->socketWith('ignored'));

        $this->assertSame(Resource::EOF, $body->pull(10));
    }

    public function testClosesTheSocketOnceTheBodyEnds(): void
    {
        $socket = $this->socketWith("2\r\nok\r\n0\r\n\r\n");
        $body = ResponseBody::chunked($socket);

        $this->drain($body, 10);

        $this->assertFalse(is_resource($socket));
    }

    /**
     * @return resource
     */
    private function socketWith(string $bytes)
    {
        $socket = fopen('php://memory', 'r+');
        fwrite($socket, $bytes);
        rewind($socket);

        return $socket;
    }

    private function drain(ResponseBody $body, int $bytes): string
    {
        $read = '';
        while (Resource::EOF !== ($chunk = $body->pull($bytes))) {
            $read .= $chunk;
        }

        return $read;
    }
}
