<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Functions\middleware {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Request;
    use Phunkie\Http4p\Response;

    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Http4p\Functions\Headers;
    use function Phunkie\Http4p\Functions\Response;
    use function Phunkie\Http4p\Functions\StatusOk;

    /**
     * Apply middleware stack to a handler.
     * 
     * @param callable(Request): IO<Response> $handler
     * @param callable ...$middlewares Each takes the next handler and returns a wrapped handler
     * @return callable(Request): IO<Response>
     */
    function Through(callable $handler, callable ...$middlewares): callable
    {
        $wrapped = $handler;
        // Apply in reverse order so the last argument wraps the handler first (inner), 
        // and the first argument wraps that (outer).
        // e.g. Through($app, A, B) -> A(B($app)) -> A executes first.
        foreach (array_reverse($middlewares) as $mw) {
            $wrapped = $mw($wrapped);
        }
        return $wrapped;
    }

    /**
     * Logger Middleware.
     * Logs request method, URI and response status to error log.
     */
    function Logger(): callable
    {
        return function (callable $next): callable {
            return function (Request $req) use ($next): IO {
                return io(function() use ($req) {
                    error_log(sprintf("[%s] %s %s", date('Y-m-d H:i:s'), $req->method->name, $req->uri));
                })->flatMap(fn() => $next($req))
                  ->map(function (Response $res) {
                      // We can't easily log status here purely without another IO, 
                      // but map transforms the value. To log, we should use evalTap if we want pure.
                      // But error_log is side effect.
                      error_log(sprintf("Response: %d", $res->status->code));
                      return $res;
                  });
            };
        };
    }

    /**
     * CORS Middleware.
     * Adds Access-Control headers to response.
     */
    function Cors(array $options = []): callable
    {
        $defaults = [
            'origin' => '*',
            'methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
            'headers' => 'Content-Type, Authorization',
            'max-age' => '86400'
        ];
        $config = array_merge($defaults, $options);

        return function (callable $next) use ($config): callable {
            return function (Request $req) use ($next, $config): IO {
                // Handle Preflight
                if ($req->method->name === 'OPTIONS') {
                    return io(fn () => Response(StatusOk(), Headers([
                        'Access-Control-Allow-Origin' => $config['origin'],
                        'Access-Control-Allow-Methods' => $config['methods'],
                        'Access-Control-Allow-Headers' => $config['headers'],
                        'Access-Control-Max-Age' => $config['max-age'],
                    ])));
                }

                return $next($req)->map(function (Response $res) use ($config) {
                    // Start with existing headers
                    $res = $res->withHeader('Access-Control-Allow-Origin', $config['origin']);
                    $res = $res->withHeader('Access-Control-Allow-Methods', $config['methods']);
                    return $res->withHeader('Access-Control-Allow-Headers', $config['headers']);
                });
            };
        };
    }
}
