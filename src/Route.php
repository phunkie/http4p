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

/**
 * A route definition: pattern + handler.
 *
 * @psalm-immutable
 */
final readonly class Route
{
    /**
     * @param Method $method
     * @param string $pattern Path pattern (e.g., "/users/:id")
     * @param callable(Request): IO<Response> $handler
     */
    public function __construct(
        public Method $method,
        public string $pattern,
        public mixed $handler
    ) {}

    /**
     * Check if this route matches the request.
     */
    public function matches(Request $request): bool
    {
        if ($this->method !== $request->method) {
            return false;
        }

        return $this->matchesPath($request->uri);
    }

    /**
     * Extract path parameters from the request URI.
     *
     * @return array<string, mixed>|null
     */
    public function extractParams(string $uri): ?array
    {
        $pattern = $this->convertPatternToRegex($this->pattern);

        if (! preg_match($pattern, $this->path($uri), $matches)) {
            return null;
        }

        // Extract named parameters
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                // Type coercion: try to convert to int if numeric
                $params[$key] = is_numeric($value) ? (int) $value : $value;
            }
        }

        return $params;
    }

    private function matchesPath(string $uri): bool
    {
        $pattern = $this->convertPatternToRegex($this->pattern);

        return preg_match($pattern, $this->path($uri)) === 1;
    }

    private function path(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) ? $path : $uri;
    }

    private function convertPatternToRegex(string $pattern): string
    {
        // Convert /users/:id to /users/(?<id>[^/]+)
        $regex = preg_replace('/:([\w]+)/', '(?<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        return $regex;
    }
}
