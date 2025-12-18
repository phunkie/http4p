<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_response_constructor()
    {
        $status = StatusOk();
        $headers = Headers(['content-type' => 'text/html']);
        $body = '<h1>Hello</h1>';
        
        $response = Response($status, $headers, $body);
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals('text/html', $response->headers->get('content-type'));
        $this->assertEquals($body, $response->body);
    }

    public function test_response_with_defaults()
    {
        $status = StatusOk();
        $response = Response($status);
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals([], $response->headers->toArray());
        $this->assertEquals('', $response->body);
    }

    public function test_response_with_status()
    {
        $response = Response(StatusOk());
        $updated = $response->withStatus(StatusNotFound());
        
        $this->assertEquals(200, $response->status->code); // Original unchanged
        $this->assertEquals(404, $updated->status->code);
    }

    public function test_response_with_headers()
    {
        $response = Response(StatusOk());
        $newHeaders = Headers(['content-type' => 'application/json']);
        $updated = $response->withHeaders($newHeaders);
        
        $this->assertEquals([], $response->headers->toArray()); // Original unchanged
        $this->assertEquals('application/json', $updated->headers->get('content-type'));
    }

    public function test_response_with_header()
    {
        $response = Response(StatusOk());
        $updated = $response->withHeader('content-type', 'application/json');
        
        $this->assertNull($response->headers->get('content-type')); // Original unchanged
        $this->assertEquals('application/json', $updated->headers->get('content-type'));
    }

    public function test_response_with_body()
    {
        $response = Response(StatusOk(), null, 'original');
        $updated = $response->withBody('updated');
        
        $this->assertEquals('original', $response->body); // Original unchanged
        $this->assertEquals('updated', $updated->body);
    }

    public function test_response_immutability()
    {
        $original = Response(StatusOk(), null, 'test');
        $modified = $original->withStatus(StatusNotFound());
        
        $this->assertNotSame($original, $modified);
        $this->assertEquals(200, $original->status->code);
        $this->assertEquals(404, $modified->status->code);
    }
}
