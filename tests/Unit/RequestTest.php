<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\Method;

use function Phunkie\Http4p\Functions\Headers;
use function Phunkie\Http4p\Functions\Request;

class RequestTest extends TestCase
{
    public function test_request_constructor()
    {
        $request = Request(Method::GET, '/users');
        
        $this->assertEquals(Method::GET, $request->method);
        $this->assertEquals('/users', $request->uri);
        $this->assertEquals([], $request->headers->toArray());
        $this->assertEquals([''], $request->body->toArray());
        $this->assertEquals([], $request->pathParams);
    }

    public function test_request_with_headers()
    {
        $headers = Headers(['content-type' => 'application/json']);
        $request = Request(Method::POST, '/users', $headers);
        
        $this->assertEquals('application/json', $request->headers->get('content-type'));
    }

    public function test_request_with_body()
    {
        $body = ['name' => 'Alice'];
        $request = Request(Method::POST, '/users', null, $body);
        
        $this->assertEquals([$body], $request->body->toArray());
    }

    public function test_request_with_path_params()
    {
        $request = Request(Method::GET, '/users/123');
        $withParams = $request->withPathParams(['id' => 123]);
        
        $this->assertEquals([], $request->pathParams); // Original unchanged
        $this->assertEquals(['id' => 123], $withParams->pathParams);
    }

    public function test_request_with_body_method()
    {
        $request = Request(Method::GET, '/users');
        $withBody = $request->withBody(Stream(['data' => 'test']));
        
        $this->assertEquals([''], $request->body->toArray()); // Original unchanged
        $this->assertEquals([['data' => 'test']], $withBody->body->toArray());
    }

    public function test_request_with_header_method()
    {
        $request = Request(Method::GET, '/users');
        $withHeader = $request->withHeader('authorization', 'Bearer token');
        
        $this->assertNull($request->headers->get('authorization')); // Original unchanged
        $this->assertEquals('Bearer token', $withHeader->headers->get('authorization'));
    }

    public function test_request_immutability()
    {
        $original = Request(Method::GET, '/test');
        $modified = $original->withBody(Stream(['foo' => 'bar']));
        
        $this->assertNotSame($original, $modified);
        $this->assertEquals([''], $original->body->toArray());
        $this->assertEquals([['foo' => 'bar']], $modified->body->toArray());
    }
}
