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

use function Phunkie\Http4p\Functions\Request;
use function Phunkie\Http4p\Functions\decode;

final class DecodingTest extends TestCase
{
    public function testDecodesAJsonBodyByDefault(): void
    {
        $request = Request(Method::POST, '/users', null, '{"name":"Ada"}');

        $this->assertSame(['name' => 'Ada'], decode($request)->unsafeRun());
    }

    public function testDecodesWithACustomDecoder(): void
    {
        $request = Request(Method::POST, '/users', null, 'a,b');

        $this->assertSame(['a', 'b'], decode($request, fn (string $body) => explode(',', $body))->unsafeRun());
    }
}
