<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function test_status_constructor()
    {
        $status = Status(418, 'I\'m a teapot');
        
        $this->assertEquals(418, $status->code);
        $this->assertEquals('I\'m a teapot', $status->reason);
    }

    public function test_status_ok()
    {
        $status = StatusOk();
        
        $this->assertEquals(200, $status->code);
        $this->assertEquals('OK', $status->reason);
    }

    public function test_status_created()
    {
        $status = StatusCreated();
        
        $this->assertEquals(201, $status->code);
        $this->assertEquals('Created', $status->reason);
    }

    public function test_status_not_found()
    {
        $status = StatusNotFound();
        
        $this->assertEquals(404, $status->code);
        $this->assertEquals('Not Found', $status->reason);
    }

    public function test_status_internal_server_error()
    {
        $status = StatusInternalServerError();
        
        $this->assertEquals(500, $status->code);
        $this->assertEquals('Internal Server Error', $status->reason);
    }
}
