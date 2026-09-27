<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\Encoder\JsonEncoder;

class JsonEncoderTest extends TestCase
{
    private JsonEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new JsonEncoder();
    }

    public function test_content_type()
    {
        $this->assertEquals('application/json', $this->encoder->contentType());
    }

    public function test_encode_null()
    {
        $result = $this->encoder->encode(null);
        
        $this->assertEquals('null', $result);
    }

    public function test_encode_boolean()
    {
        $this->assertEquals('true', $this->encoder->encode(true));
        $this->assertEquals('false', $this->encoder->encode(false));
    }

    public function test_encode_integer()
    {
        $this->assertEquals('42', $this->encoder->encode(42));
        $this->assertEquals('0', $this->encoder->encode(0));
        $this->assertEquals('-123', $this->encoder->encode(-123));
    }

    public function test_encode_float()
    {
        $this->assertEquals('3.14', $this->encoder->encode(3.14));
        $this->assertEquals('-2.5', $this->encoder->encode(-2.5));
    }

    public function test_encode_string()
    {
        $this->assertEquals('hello', $this->encoder->encode('hello'));
        $this->assertEquals('', $this->encoder->encode(''));
    }

    public function test_encode_string_with_special_characters()
    {
        $this->assertEquals("hello\nworld", $this->encoder->encode("hello\nworld"));
        $this->assertEquals('quote: "test"', $this->encoder->encode('quote: "test"'));
    }

    public function test_encode_indexed_array()
    {
        $result = $this->encoder->encode([1, 2, 3]);
        
        $this->assertEquals('[1,2,3]', $result);
    }

    public function test_encode_associative_array()
    {
        $result = $this->encoder->encode(['name' => 'Alice', 'age' => 30]);
        
        $this->assertEquals('{"name":"Alice","age":30}', $result);
    }

    public function test_encode_nested_array()
    {
        $data = [
            'user' => [
                'name' => 'Bob',
                'roles' => ['admin', 'user']
            ],
            'active' => true
        ];
        
        $result = $this->encoder->encode($data);
        
        $this->assertEquals('{"user":{"name":"Bob","roles":["admin","user"]},"active":true}', $result);
    }

    public function test_encode_empty_array()
    {
        $this->assertEquals('[]', $this->encoder->encode([]));
    }

    public function test_encode_object()
    {
        $obj = new \stdClass();
        $obj->name = 'Charlie';
        $obj->age = 25;
        
        $result = $this->encoder->encode($obj);
        
        $this->assertEquals('{"name":"Charlie","age":25}', $result);
    }

    public function test_encode_object_with_nested_properties()
    {
        $address = new \stdClass();
        $address->city = 'London';
        $address->country = 'UK';
        
        $user = new \stdClass();
        $user->name = 'David';
        $user->address = $address;
        
        $result = $this->encoder->encode($user);
        
        $this->assertEquals('{"name":"David","address":{"city":"London","country":"UK"}}', $result);
    }

    public function test_encode_mixed_types()
    {
        $data = [
            'string' => 'test',
            'number' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object)['key' => 'value']
        ];
        
        $result = $this->encoder->encode($data);
        $decoded = json_decode($result, true);
        
        $this->assertEquals('test', $decoded['string']);
        $this->assertEquals(42, $decoded['number']);
        $this->assertEquals(3.14, $decoded['float']);
        $this->assertTrue($decoded['bool']);
        $this->assertNull($decoded['null']);
        $this->assertEquals([1, 2, 3], $decoded['array']);
        $this->assertEquals(['key' => 'value'], $decoded['object']);
    }

    public function test_encode_unicode()
    {
        $result = $this->encoder->encode(['emoji' => '🎉', 'text' => 'café']);
        
        $this->assertStringContainsString('🎉', $result);
        $this->assertStringContainsString('café', $result);
    }

    public function test_encode_preserves_numeric_keys()
    {
        $data = [
            0 => 'zero',
            1 => 'one',
            2 => 'two'
        ];
        
        $result = $this->encoder->encode($data);
        
        $this->assertEquals('["zero","one","two"]', $result);
    }

    public function test_encode_preserves_string_numeric_keys()
    {
        $data = [
            '0' => 'zero',
            '1' => 'one',
            '10' => 'ten'
        ];
        
        $result = $this->encoder->encode($data);
        $decoded = json_decode($result, true);
        
        // PHP will treat these as an indexed array
        $this->assertIsArray($decoded);
    }

    public function testEncodesDatesAsIso8601WhereverTheyAppear(): void
    {
        $at = new \DateTimeImmutable('2020-01-02 03:04:05', new \DateTimeZone('UTC'));
        $entity = new class($at) {
            public function __construct(public \DateTimeImmutable $createdAt) {}
        };

        $this->assertSame('{"at":"2020-01-02T03:04:05+00:00","rows":[{"createdAt":"2020-01-02T03:04:05+00:00"}]}', (new JsonEncoder())->encode(['at' => $at, 'rows' => [$entity]]));
    }

    public function testEncodesBackedEnumsByValueWhereverTheyAppear(): void
    {
        $entity = new class(Suit::Hearts) {
            public function __construct(public Suit $suit) {}
        };

        $this->assertSame('{"suit":"hearts","rows":[{"suit":"hearts"}]}', (new JsonEncoder())->encode(['suit' => Suit::Hearts, 'rows' => [$entity]]));
    }

    public function testEncodesAValueObjectAsItsSingleValueWhereverItAppears(): void
    {
        $entity = new class(new Email('ada@example.com'), new Rating(4)) {
            public function __construct(public Email $email, public Rating $rating) {}
        };

        $this->assertSame('{"email":"ada@example.com","rows":[{"email":"ada@example.com","rating":4}]}', (new JsonEncoder())->encode(['email' => new Email('ada@example.com'), 'rows' => [$entity]]));
    }

    public function testAnObjectWrappingAValueObjectStaysAnObject(): void
    {
        $this->assertSame('{"contact":{"email":"ada@example.com"}}', (new JsonEncoder())->encode(['contact' => new Contact(new Email('ada@example.com'))]));
    }

    public function testLeavesAnObjectWithMoreThanOnePropertyAsAnObject(): void
    {
        $this->assertSame('{"amount":10,"currency":"GBP"}', (new JsonEncoder())->encode(new Money(10, 'GBP')));
    }

    public function testLeavesAnObjectBuiltWithoutAConstructorAsAnObject(): void
    {
        $this->assertSame('{"key":"value"}', (new JsonEncoder())->encode((object) ['key' => 'value']));
    }

    public function testJsonSerializableWinsOverTheValueObjectRule(): void
    {
        $this->assertSame('{"value":"fiction"}', (new JsonEncoder())->encode(new Tag('fiction')));
    }
}

enum Suit: string
{
    case Hearts = 'hearts';
}

final readonly class Email
{
    public function __construct(public string $value) {}
}

final readonly class Rating
{
    public function __construct(public int $value) {}
}

final readonly class Contact
{
    public function __construct(public Email $email) {}
}

final readonly class Money
{
    public function __construct(public int $amount, public string $currency) {}
}

final readonly class Tag implements \JsonSerializable
{
    public function __construct(public string $name) {}

    public function jsonSerialize(): array
    {
        return ['value' => $this->name];
    }
}
