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

/**
 * HTTP Response with streaming body.
 *
 * Response<F> where F is the effect type (typically IO).
 * The body should be a Stream<F, Byte> (Phunkie\Streams\Types\Stream) allowing constant memory usage.
 *
 * For now, body is typed as mixed until Phunkie\Streams is updated to work with effect ^1.2.
 * The body can be:
 * - string (for simple responses)
 * - Stream<F, Byte> (for streaming responses - future)
 *
 * @template F
 * @psalm-immutable
 */
final readonly class Response
{
    /**
     * @param Status $status
     * @param Headers $headers
     * @param mixed $body String for now, will be Stream<F, Byte> when streams is updated
     */
    public function __construct(
        public Status $status,
        public Headers $headers,
        public mixed $body
    ) {}

    public static function of(Status $status, Headers $headers, mixed $body): self
    {
        return new self($status, $headers, $body);
    }

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

    public function withBody(mixed $body): self
    {
        return new self($this->status, $this->headers, $body);
    }
}
