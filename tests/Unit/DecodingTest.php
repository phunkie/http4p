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
use Phunkie\Http4p\DecodeFailure;
use Phunkie\Http4p\Method;

use function Phunkie\Http4p\Functions\Request;
use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\decoding\jsonObject;

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

    public function testFailsOnABodyThatIsNotJson(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::POST, '/users', null, 'not json'))->unsafeRun();
    }

    public function testJsonObjectKeepsOnlyTheNamedFields(): void
    {
        $request = Request(Method::POST, '/users', null, '{"name":"Ada","email":"ada@example.com","role":"admin"}');

        $this->assertSame(['name' => 'Ada', 'email' => 'ada@example.com'], decode($request, jsonObject('name', 'email'))->unsafeRun());
    }

    public function testJsonObjectWithoutFieldsKeepsTheWholeObject(): void
    {
        $request = Request(Method::POST, '/users', null, '{"name":"Ada"}');

        $this->assertSame(['name' => 'Ada'], decode($request, jsonObject())->unsafeRun());
    }

    public function testJsonObjectFailsWhenTheBodyIsNotAnObject(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::POST, '/users', null, '[1,2]'), jsonObject('name'))->unsafeRun();
    }

    public function testJsonObjectFailsWhenNoNamedFieldIsPresent(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::POST, '/users', null, '{"role":"admin"}'), jsonObject('name', 'email'))->unsafeRun();
    }
}
