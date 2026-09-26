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

use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\Request;

final readonly class Book
{
    public function __construct(
        #[\Phunkie\Phetch\Attributes\Generated]
        public int $id,
        #[\Phunkie\Phetch\Attributes\Generated]
        public int $authorId,
        public string $title,
        public int $publishedYear,
        public ?string $subtitle = null,
        public bool $inPrint = true,
    ) {
    }
}

final class DecodingTest extends TestCase
{
    public function testDecodesAJsonBodyByDefault(): void
    {
        $request = Request(Method::POST, '/books', null, '{"title":"Notes"}');

        $this->assertSame(['title' => 'Notes'], decode($request)->unsafeRun());
    }

    public function testDecodesWithACustomDecoder(): void
    {
        $request = Request(Method::POST, '/books', null, 'a,b');

        $this->assertSame(['a', 'b'], decode($request, fn (string $body) => explode(',', $body))->unsafeRun());
    }

    public function testFailsOnABodyThatIsNotJson(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::POST, '/books', null, 'not json'))->unsafeRun();
    }

    public function testDecodesAPostBodyIntoTheEntityFieldsKeyedByParameterName(): void
    {
        $request = Request(Method::POST, '/books', null, '{"title":"Notes","published_year":1843,"id":9,"author_id":3,"rating":5}');

        $this->assertSame(['title' => 'Notes', 'publishedYear' => 1843], decode($request, Book::class)->unsafeRun());
    }

    public function testAPostBodyMustCarryEveryFieldThatIsNeitherGeneratedNorDefaulted(): void
    {
        try {
            decode(Request(Method::POST, '/books', null, '{"title":"Notes"}'), Book::class)->unsafeRun();
            $this->fail('Expected a DecodeFailure.');
        } catch (DecodeFailure $failure) {
            $this->assertSame('Body does not describe Book.', $failure->getMessage());
            $this->assertSame(['publishedYear' => 'missing'], $failure->errors());
        }
    }

    public function testAPutBodyIsAsCompleteAsAPostBody(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::PUT, '/books/1', null, '{"title":"Notes"}'), Book::class)->unsafeRun();
    }

    public function testAPatchBodyMayCarryAnySubset(): void
    {
        $request = Request(Method::PATCH, '/books/1', null, '{"title":"Sketch"}');

        $this->assertSame(['title' => 'Sketch'], decode($request, Book::class)->unsafeRun());
    }

    public function testAPatchBodyMustCarryAtLeastOneKnownField(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::PATCH, '/books/1', null, '{"rating":5}'), Book::class)->unsafeRun();
    }

    public function testValuesMustMatchTheParameterTypes(): void
    {
        try {
            decode(Request(Method::POST, '/books', null, '{"title":7,"published_year":"1843","in_print":"yes"}'), Book::class)->unsafeRun();
            $this->fail('Expected a DecodeFailure.');
        } catch (DecodeFailure $failure) {
            $this->assertSame([
                'title' => 'expected string, got int',
                'publishedYear' => 'expected int, got string',
                'inPrint' => 'expected bool, got string',
            ], $failure->errors());
        }
    }

    public function testNullableAndDefaultedParametersAreOptionalAndKeptWhenSent(): void
    {
        $request = Request(Method::POST, '/books', null, '{"title":"Notes","published_year":1843,"subtitle":null,"in_print":false}');

        $this->assertSame(
            ['title' => 'Notes', 'publishedYear' => 1843, 'subtitle' => null, 'inPrint' => false],
            decode($request, Book::class)->unsafeRun()
        );
    }

    public function testTheBodyMustBeAJsonObject(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::POST, '/books', null, '[1,2]'), Book::class)->unsafeRun();
    }
}
