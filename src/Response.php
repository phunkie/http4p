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
 * HTTP Response with streaming body.
 * An HTTP Response.
 *
 * @psalm-immutable
 */
final readonly class Response
{
    /**
     * @param Status $status
     * @param Headers $headers
     * @param Stream $body
     */
    public function __construct(
        public Status $status,
        public Headers $headers,
        public Stream $body
    ) {}

    public function withStatus(Status $status): self
    {
        return new self($status, $this->headers, $this->body);
    }

    public function withHeaders(Headers $headers): self
    {
        return new self($this->status, $headers, $this->body);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->status, $this->headers->put($name, $value), $this->body);
    }

    public function withBody(Stream $body): self
    {
        return new self($this->status, $this->headers, $body);
    }
}
