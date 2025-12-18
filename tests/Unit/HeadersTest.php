<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HeadersTest extends TestCase
{
    public function test_empty_headers()
    {
        $headers = Headers();
        
        $this->assertNull($headers->get('content-type'));
        $this->assertEquals([], $headers->toArray());
    }

    public function test_headers_with_values()
    {
        $headers = Headers([
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ]);
        
        $this->assertEquals('application/json', $headers->get('content-type'));
        $this->assertEquals('application/json', $headers->get('accept'));
    }

    public function test_headers_are_case_insensitive()
    {
        $headers = Headers()->put('Content-Type', 'application/json');
        
        $this->assertEquals('application/json', $headers->get('content-type'));
        $this->assertEquals('application/json', $headers->get('Content-Type'));
        $this->assertEquals('application/json', $headers->get('CONTENT-TYPE'));
    }

    public function test_headers_put()
    {
        $headers = Headers();
        $updated = $headers->put('content-type', 'text/html');
        
        $this->assertNull($headers->get('content-type')); // Original unchanged
        $this->assertEquals('text/html', $updated->get('content-type'));
    }

    public function test_headers_remove()
    {
        $headers = Headers(['content-type' => 'application/json']);
        $removed = $headers->remove('content-type');
        
        $this->assertEquals('application/json', $headers->get('content-type')); // Original unchanged
        $this->assertNull($removed->get('content-type'));
    }

    public function test_headers_immutability()
    {
        $original = Headers(['foo' => 'bar']);
        $modified = $original->put('baz', 'qux');
        
        $this->assertNotSame($original, $modified);
        $this->assertEquals(['foo' => 'bar'], $original->toArray());
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $modified->toArray());
    }
}
