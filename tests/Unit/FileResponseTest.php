<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phunkie\Effect\IO\IO;
use Phunkie\Streams\Type\Stream;

use function Phunkie\Http4p\Functions\FileResponse;

class FileResponseTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/test_file_response.txt';
        file_put_contents($this->tempFile, "Hello World");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function test_file_response_success()
    {
        $io = FileResponse($this->tempFile);
        
        $response = $io->unsafeRun();
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals('text/plain', $response->headers->get('content-type'));
        $this->assertEquals('11', $response->headers->get('content-length'));
        
        $this->assertInstanceOf(Stream::class, $response->body);
        
        // compiling stream from file returns chunks or content?
        // Stream(Path) -> ResourcePull -> emits chunks.
        // We can check toArray() if bytes >= file size?
        // Default bytes is small.
        // toArray() on Resource stream might fail if it's considered effectful?
        // Stream::fromResource returns Stream with ResourcePull.
        // Stream::toArray() throws "Can only call toArray on Pure Streams".
        
        // So we cannot check body content directly via toArray().
        // We can check if it's effectful?
        // Or compile and runLog?
        
        // $this->assertNotEmpty($response->body);
    }

    public function test_file_response_not_found()
    {
        $io = FileResponse('/non/existent/file.txt');
        
        $response = $io->unsafeRun();
        
        $this->assertEquals(404, $response->status->code);
        $this->assertStringContainsString('File not found', $response->body->toArray()[0] ?? '');
        // Note: NotFound returns pure stream with error message string.
    }
}
