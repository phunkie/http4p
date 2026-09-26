<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p;

use Phunkie\Effect\IO\IO;
use Phunkie\Http4p\Encoder\JsonEncoder;
use Phunkie\Types\ImmList;
use Throwable;

use function Phunkie\Http4p\Functions\Headers;
use function Phunkie\Http4p\Functions\Response;
use function Phunkie\Http4p\Functions\StatusBadRequest;
use function Phunkie\Http4p\Functions\response\NotFound;

/**
 * Routes HTTP requests to handlers.
 */
final class Router
{
    /**
     * @param ImmList<Route> $routes
     */
    public function __construct(
        private readonly ImmList $routes
    ) {}

    /**
     * Route a request to a handler.
     *
     * @return IO<Response>
     */
    public function route(Request $request): IO
    {
        foreach ($this->routes->toArray() as $route) {
            if ($route->matches($request)) {
                $params = $route->extractParams($request->uri);
                $requestWithParams = $request->withPathParams($params ?? []);

                return ($route->handler)($requestWithParams)
                    ->handleError(fn (Throwable $e) => $e instanceof DecodeFailure ? $this->badRequest($e) : throw $e);
            }
        }

        // No route matched - 404
        return NotFound(['error' => 'Not Found', 'path' => $request->uri]);
    }

    public function __invoke(Request $request): IO
    {
        return $this->route($request);
    }

    private function badRequest(DecodeFailure $failure): Response
    {
        return Response(
            StatusBadRequest(),
            Headers(['content-type' => 'application/json']),
            (new JsonEncoder())->encode(['error' => $failure->getMessage()] + ([] === $failure->errors() ? [] : ['errors' => $failure->errors()])),
        );
    }
}
