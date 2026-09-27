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
use Phunkie\Http4p\Encoder\JsonEncoder;
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Request;
use Phunkie\Http4p\Response;
use Phunkie\Http4p\Route;
use Phunkie\Http4p\Router;
use Phunkie\Streams\IO\File\Path;
use Phunkie\Types\ImmList;
use Throwable;

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Http4p\Functions\Headers;
use function Phunkie\Http4p\Functions\Response;
use function Phunkie\Http4p\Functions\StatusInternalServerError;

/**
 * Serves an application through PHP's own SAPI: one request per PHP process, as php-fpm, the
 * built-in server, FrankenPHP or RoadRunner run it.
 *
 * The response body is written one chunk at a time and each chunk is flushed before the next one
 * is produced, so a streamed body reaches the client as it is generated.
 */
final class PhpServer
{
    /**
     * @var callable(Request): IO<Response>
     */
    private $handler;

    private Output $output;

    /**
     * @param ImmList<Route>|callable(Request): IO<Response> $app
     * @param Output|null $output where the response is written; the SAPI when not given
     */
    public function __construct(ImmList|callable $app, ?Output $output = null)
    {
        if ($app instanceof ImmList) {
            $router = new Router($app);
            $this->handler = fn(Request $req) => $router->route($req);
        } else {
            $this->handler = $app;
        }
        $this->output = $output ?? new SapiOutput();
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
        })->flatMap(fn ($request) => ($this->handler)($request))
            ->handleError(fn (Throwable $e) => Response(
                StatusInternalServerError(),
                Headers(['content-type' => 'application/json']),
                (new JsonEncoder())->encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]),
            ));
    }

    /**
     * Send a response to the client: the status and headers, then the body chunk by chunk, each
     * chunk written before the next one is pulled.
     *
     * @return IO<int> Exit code
     */
    public function sendResponse(Response $response): IO
    {
        return io(function () use ($response) {
            $this->output->status($response->status->code);
            foreach ($response->headers->toArray() as $name => $value) {
                $this->output->header($name, $value);
            }
        })->flatMap(
            fn() => $response->body
                ->evalTap(fn($chunk) => io(fn() => $this->output->write($chunk)))
                ->compile()
                ->drain()
        )->map(fn() => 0);
    }

    /**
     * Run the server (handle request and send response).
     *
     * @return IO<int>
     */
    public function run(int $port = 8000): IO
    {
        if (php_sapi_name() === 'cli') {
            return io(function () use ($port) {
                echo "Starting server at http://localhost:$port\n";
                echo "Press Ctrl+C to stop.\n";
                $script = $_SERVER['SCRIPT_FILENAME'];
                passthru("php -S localhost:$port $script");
                return 0;
            });
        }

        return $this->handleRequest()
            ->flatMap(fn ($response) => $this->sendResponse($response));
    }
}
