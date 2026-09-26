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

use function Phunkie\Http4p\Functions\response\Conflict;

final class ConflictTest extends TestCase
{
    public function testConflictIsA409WithAJsonBody(): void
    {
        $response = Conflict(['error' => 'Email already taken.'])->unsafeRun();

        $this->assertSame(409, $response->status->code);
        $this->assertSame('{"error":"Email already taken."}', implode('', $response->body->toArray()));
    }
}
