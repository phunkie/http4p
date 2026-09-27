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
use Tests\Integration\Fixtures\Book;

use function Phunkie\Http4p\Functions\decodeLines;
use function Phunkie\Http4p\Functions\response\Ok;

final class DecodeLinesTest extends TestCase
{
    public function testDecodesOneEntityPerLineWhateverTheChunkBoundaries(): void
    {
        $response = Ok(\Stream('{"id":1,"title":"Notes","author":"Ada","inStock":true}' . "\n" . '{"id":2,"ti', 'tle":"Sketch","author":"Ada","inStock":false}' . "\n"))->unsafeRun();

        $books = decodeLines($response, Book::class)->compile()->toArray();

        $this->assertEquals([new Book(1, 'Notes', 'Ada', true), new Book(2, 'Sketch', 'Ada', false)], $books);
    }

    public function testSkipsBlankLinesAndAcceptsAMissingFinalNewline(): void
    {
        $response = Ok(\Stream("\n" . '{"id":1,"title":"Notes","author":"Ada","inStock":true}' . "\n\n" . '{"id":2,"title":"Sketch","author":"Ada","inStock":false}'))->unsafeRun();

        $this->assertCount(2, decodeLines($response, Book::class)->compile()->toArray());
    }

    public function testFailsTheStreamWhenItReachesTheLineThatDoesNotFit(): void
    {
        $response = Ok(\Stream('{"id":1,"title":"Notes","author":"Ada","inStock":true}' . "\n", '{"id":"two","title":"Sketch"}' . "\n"))->unsafeRun();
        $seen = [];

        try {
            decodeLines($response, Book::class)
                ->evalTap(function (Book $book) use (&$seen) {
                    return \Phunkie\Effect\Functions\io\io(function () use (&$seen, $book) {
                        $seen[] = $book->id;
                    });
                })
                ->compile()
                ->drain()
                ->unsafeRun();
            $this->fail('Expected a DecodeFailure.');
        } catch (DecodeFailure $e) {
            $this->assertSame(['id' => 'expected int, got string', 'author' => 'missing', 'inStock' => 'missing'], $e->errors());
        }

        $this->assertSame([1], $seen);
    }
}
