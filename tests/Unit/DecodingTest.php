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

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\DecodeFailure;
use Phunkie\Http4p\Method;

use function Phunkie\Http4p\Functions\decode;
use function Phunkie\Http4p\Functions\Request;

enum Format: string
{
    case Hardcover = 'hardcover';
    case Ebook = 'ebook';
}

final readonly class Isbn
{
    public function __construct(public string $value)
    {
        if (! preg_match('/^\d{13}$/', $value)) {
            throw new InvalidArgumentException('must be 13 digits');
        }
    }
}

final readonly class Edition
{
    public function __construct(
        #[\Phunkie\Phetch\Attributes\Generated]
        public int $id,
        public int $bookId,
        public Isbn $isbn,
        public Format $format,
        public DateTimeImmutable $publishedOn,
        public ?float $price = null,
    ) {
    }
}

final readonly class Book
{
    public function __construct(
        #[\Phunkie\Phetch\Attributes\Generated]
        public int $id,
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

        $this->assertSame(['authorId' => 3, 'title' => 'Notes', 'publishedYear' => 1843], decode($request, Book::class)->unsafeRun());
    }

    public function testAPostBodyMustCarryEveryFieldThatIsNeitherGeneratedNorDefaulted(): void
    {
        try {
            decode(Request(Method::POST, '/books', null, '{"title":"Notes"}'), Book::class)->unsafeRun();
            $this->fail('Expected a DecodeFailure.');
        } catch (DecodeFailure $failure) {
            $this->assertSame('Body does not describe Book.', $failure->getMessage());
            $this->assertSame(['authorId' => 'missing', 'publishedYear' => 'missing'], $failure->errors());
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
            decode(Request(Method::POST, '/books', null, '{"author_id":1,"title":7,"published_year":"1843","in_print":"yes"}'), Book::class)->unsafeRun();
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
        $request = Request(Method::POST, '/books', null, '{"author_id":1,"title":"Notes","published_year":1843,"subtitle":null,"in_print":false}');

        $this->assertSame(
            ['authorId' => 1, 'title' => 'Notes', 'publishedYear' => 1843, 'subtitle' => null, 'inPrint' => false],
            decode($request, Book::class)->unsafeRun()
        );
    }

    public function testTheBodyMustBeAJsonObject(): void
    {
        $this->expectException(DecodeFailure::class);

        decode(Request(Method::POST, '/books', null, '[1,2]'), Book::class)->unsafeRun();
    }

    public function testPathParametersFillMatchingConstructorParameters(): void
    {
        $request = Request(Method::POST, '/authors/3/books', null, '{"title":"Notes","published_year":1843}')->withPathParams(['authorId' => 3]);

        $this->assertSame(['authorId' => 3, 'title' => 'Notes', 'publishedYear' => 1843], decode($request, Book::class)->unsafeRun());
    }

    public function testAPathParameterWinsOverTheBody(): void
    {
        $request = Request(Method::POST, '/authors/3/books', null, '{"author_id":9,"title":"Notes","published_year":1843}')->withPathParams(['authorId' => 3]);

        $this->assertSame(3, decode($request, Book::class)->unsafeRun()['authorId']);
    }

    public function testProvidedValuesFillParametersAndAreNotRequired(): void
    {
        $request = Request(Method::POST, '/books', null, '{"title":"Notes","published_year":1843}');

        $this->assertSame(['authorId' => 3, 'title' => 'Notes', 'publishedYear' => 1843], decode($request, Book::class, ['authorId' => 3])->unsafeRun());
    }

    public function testValueObjectsEnumsAndDatesAreBuiltFromScalars(): void
    {
        $request = Request(Method::POST, '/books/1/editions', null, '{"isbn":"9780000000002","format":"ebook","published_on":"2020-01-02","price":9.5}')
            ->withPathParams(['bookId' => 1]);

        $this->assertEquals(
            ['bookId' => 1, 'isbn' => new Isbn('9780000000002'), 'format' => Format::Ebook, 'publishedOn' => new DateTimeImmutable('2020-01-02'), 'price' => 9.5],
            decode($request, Edition::class)->unsafeRun()
        );
    }

    public function testInvalidValueObjectsEnumsAndDatesAreReportedPerField(): void
    {
        $request = Request(Method::POST, '/books/1/editions', null, '{"isbn":"12","format":"vinyl","published_on":"never"}')
            ->withPathParams(['bookId' => 1]);

        try {
            decode($request, Edition::class)->unsafeRun();
            $this->fail('Expected a DecodeFailure.');
        } catch (DecodeFailure $failure) {
            $this->assertSame([
                'isbn' => 'must be 13 digits',
                'format' => 'expected one of hardcover, ebook, got "vinyl"',
                'publishedOn' => 'expected a date, got "never"',
            ], $failure->errors());
        }
    }
}
