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

use Phunkie\Streams\Type\Stream;

/**
 * HTTP Request.
 *
 * @psalm-immutable
 */
final readonly class Request
{
    /**
     * @param Method $method
     * @param string $uri
     * @param Headers $headers
     * @param Stream $body
     * @param array<string, mixed> $pathParams Extracted path parameters
     */
    public function __construct(
        public Method $method,
        public string $uri,
        public Headers $headers,
        public Stream $body,
        public array $pathParams = []
    ) {}

    /**
     * The value of one query string parameter, or null when absent.
     */
    public function query(string $name): ?string
    {
        $value = $this->queryParams()[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * The query string parsed into an array, empty when there is none.
     *
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        parse_str((string) parse_url($this->uri, PHP_URL_QUERY), $params);

        return $params;
    }

    public function withPathParams(array $params): self
    {
        return new self($this->method, $this->uri, $this->headers, $this->body, $params);
    }

    public function withBody(Stream $body): self
    {
        return new self($this->method, $this->uri, $this->headers, $body, $this->pathParams);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->method, $this->uri, $this->headers->put($name, $value), $this->body, $this->pathParams);
    }
}
