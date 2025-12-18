<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ResponseConstructorsTest extends TestCase
{
    public function test_ok_response()
    {
        $response = Ok(['message' => 'success'])->unsafeRun();
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals('{"message":"success"}', $response->body);
    }

    public function test_created_response()
    {
        $response = Created(['id' => 1])->unsafeRun();
        
        $this->assertEquals(201, $response->status->code);
        $this->assertEquals('{"id":1}', $response->body);
    }

    public function test_no_content_response()
    {
        $response = NoContent()->unsafeRun();
        
        $this->assertEquals(204, $response->status->code);
        $this->assertEquals('', $response->body);
    }

    public function test_bad_request_response()
    {
        $response = BadRequest(['error' => 'Invalid input'])->unsafeRun();
        
        $this->assertEquals(400, $response->status->code);
        $this->assertEquals('{"error":"Invalid input"}', $response->body);
    }

    public function test_not_found_response()
    {
        $response = NotFound(['error' => 'Resource not found'])->unsafeRun();
        
        $this->assertEquals(404, $response->status->code);
        $this->assertEquals('{"error":"Resource not found"}', $response->body);
    }

    public function test_internal_server_error_response()
    {
        $response = InternalServerError(['error' => 'Something went wrong'])->unsafeRun();
        
        $this->assertEquals(500, $response->status->code);
        $this->assertEquals('{"error":"Something went wrong"}', $response->body);
    }

    public function test_response_with_null_body()
    {
        $response = Ok(null)->unsafeRun();
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals('', $response->body);
    }

    public function test_response_returns_io()
    {
        $io = Ok(['test' => true]);
        
        $this->assertInstanceOf(\Phunkie\Effect\IO\IO::class, $io);
    }
}
