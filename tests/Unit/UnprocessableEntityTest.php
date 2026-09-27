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

use function Phunkie\Http4p\Functions\response\UnprocessableEntity;

final class UnprocessableEntityTest extends TestCase
{
    public function testUnprocessableEntityIsA422WithAJsonBody(): void
    {
        $response = UnprocessableEntity(['error' => 'The publisher does not exist.'])->unsafeRun();

        $this->assertSame(422, $response->status->code);
        $this->assertSame('Unprocessable Entity', $response->status->reason);
        $this->assertSame('{"error":"The publisher does not exist."}', implode('', $response->body->toArray()));
    }
}
