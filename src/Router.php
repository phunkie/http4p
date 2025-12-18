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
use Phunkie\Types\ImmList;

use function Phunkie\Effect\Functions\io\io;
use function NotFound;

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

                return ($route->handler)($requestWithParams);
            }
        }

        // No route matched - 404
        return NotFound(['error' => 'Not Found', 'path' => $request->uri]);
    }

    public function __invoke(Request $request): IO
    {
        return $this->route($request);
    }
}
