<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Functions\routes {

    use Phunkie\Http4p\Method;
    use Phunkie\Http4p\Route;


    /**
     * Define a GET route.
     *
     * @param string $pattern Path pattern (e.g., "/users/:id")
     * @param callable $handler Handler function that receives extracted params and returns IO<Response>
     * @return Route
     */
    function GET(string $pattern, callable $handler): Route
    {
        return new Route(Method::GET, $pattern, wrapHandler($pattern, $handler));
    }

    /**
     * Define a POST route.
     *
     * @param string $pattern
     * @param callable $handler
     * @return Route
     */
    function POST(string $pattern, callable $handler): Route
    {
        return new Route(Method::POST, $pattern, wrapHandler($pattern, $handler));
    }

    /**
     * Define a PUT route.
     *
     * @param string $pattern
     * @param callable $handler
     * @return Route
     */
    function PUT(string $pattern, callable $handler): Route
    {
        return new Route(Method::PUT, $pattern, wrapHandler($pattern, $handler));
    }

    /**
     * Define a PATCH route.
     *
     * @param string $pattern
     * @param callable $handler
     * @return Route
     */
    function PATCH(string $pattern, callable $handler): Route
    {
        return new Route(Method::PATCH, $pattern, wrapHandler($pattern, $handler));
    }

    /**
     * Define a DELETE route.
     *
     * @param string $pattern
     * @param callable $handler
     * @return Route
     */
    function DELETE(string $pattern, callable $handler): Route
    {
        return new Route(Method::DELETE, $pattern, wrapHandler($pattern, $handler));
    }

}

namespace Phunkie\Http4p\Functions\routes {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Request;
    use ReflectionNamedType;
    use ReflectionParameter;

    /**
     * Wrap a user handler to extract path params and pass them as arguments.
     *
     * @internal
     */
    function wrapHandler(string $pattern, callable $handler): callable
    {
        return function (Request $request) use ($pattern, $handler): IO {
            // Extract parameter names from pattern
            preg_match_all('/:(\w+)/', $pattern, $matches);
            $paramNames = $matches[1];

            // Build arguments for handler
            $args = [];
            foreach ($paramNames as $name) {
                if (isset($request->pathParams[$name])) {
                    $args[] = $request->pathParams[$name];
                }
            }

            // If handler expects Request, add it
            $reflection = new \ReflectionFunction($handler);
            $params = $reflection->getParameters();

            if (! empty($params)) {
                // Check if first param is Request (when no path params)
                $firstParam = $params[0];
                if (empty($args) && expectsRequest($firstParam)) {
                    $args[] = $request;
                } else {
                    // Check if last param is Request (after path params)
                    $lastParam = end($params);
                    if (expectsRequest($lastParam)) {
                        $args[] = $request;
                    }
                }
            }

            // Call handler with extracted arguments
            return $handler(...$args);
        };
    }

    /**
     * @internal
     */
    function expectsRequest(ReflectionParameter $param): bool
    {
        $type = $param->getType();

        return $type instanceof ReflectionNamedType && $type->getName() === Request::class;
    }
}

namespace Phunkie\Http4p\Functions {

    use Phunkie\Http4p\Route;
    use Phunkie\Types\ImmList;

    use function ImmList;

    /**
     * Create an HttpRoutes collection.
     *
     * @param Route ...$routes
     * @return ImmList<Route>
     */
    function HttpRoutes(Route ...$routes): ImmList
    {
        return ImmList(...$routes);
    }
}
