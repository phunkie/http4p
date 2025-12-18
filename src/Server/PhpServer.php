<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Server;

use Phunkie\Effect\IO\IO;
use Phunkie\Http4p\Headers;
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Request;
use Phunkie\Http4p\Response;
use Phunkie\Http4p\Router;
use Phunkie\Streams\IO\File\Path;
use Phunkie\Types\ImmList;

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Http4p\Functions\InternalServerError;

/**
 * Simple HTTP server using PHP's built-in capabilities.
 *
 * This is a basic implementation for development/testing.
 * For production, use a proper server like ReactPHP or Swoole.
 */
final class PhpServer
{
    private Router $router;

    /**
     * @param ImmList<Route> $routes
     */
    public function __construct(ImmList $routes)
    {
        $this->router = new Router($routes);
    }

    /**
     * Handle an incoming HTTP request.
     *
     * This is called by the server entry point (e.g., index.php).
     *
     * @return IO<Response>
     */
    public function handleRequest(): IO
    {
        return io(function () {
            // Build request from PHP globals
            $method = Method::from($_SERVER['REQUEST_METHOD']);
            $uri = $_SERVER['REQUEST_URI'];

            // Parse headers
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                    $headers[$headerName] = $value;
                }
            }

            // Get body as Stream
             $body = \Stream(new Path('php://input'));

            return new Request($method, $uri, Headers($headers), $body);
        })->flatMap(fn ($request) => $this->router->route($request))
            ->handleError(fn ($e) => InternalServerError([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ]));
    }

    /**
     * Send a response to the client.
     *
     * @return IO<int> Exit code
     */
    public function sendResponse(Response $response): IO
    {
        return io(function () use ($response) {
            // Set status
            http_response_code($response->status->code);

            // Set headers
            foreach ($response->headers->toArray() as $name => $value) {
                header("$name: $value");
            }
        })->flatMap(fn() => 
            $response->body
                ->evalTap(fn($chunk) => io(function () use ($chunk) { echo $chunk; }))
                ->compile()
                ->drain()
        )->map(fn() => 0);
    }

    /**
     * Run the server (handle request and send response).
     *
     * @return IO<int>
     */
    public function run(): IO
    {
        return $this->handleRequest()
            ->flatMap(fn ($response) => $this->sendResponse($response));
    }
}
