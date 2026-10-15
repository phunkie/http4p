<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Client;

use Phunkie\Streams\IO\Resource as Source;

/**
 * The body of a response read from the socket as it is pulled: chunked transfer encoding decoded
 * chunk by chunk, a Content-Length counted down, or everything until the server closes. The socket
 * is closed when the body ends.
 */
final class ResponseBody implements Source
{
    private const CHUNKED = 'chunked';
    private const COUNTED = 'counted';
    private const UNTIL_CLOSE = 'until close';

    private bool $done = false;

    /**
     * @param resource|null $socket
     * @param int $remaining bytes left in the current chunk, or in the whole body when counted
     */
    private function __construct(private $socket, private string $framing, private int $remaining = 0)
    {
        $this->done = null === $socket;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param resource $socket
     */
    public static function chunked($socket): self
    {
        return new self($socket, self::CHUNKED);
    }

    /**
     * @param resource $socket
     */
    public static function ofLength($socket, int $length): self
    {
        return 0 === $length ? self::none($socket) : new self($socket, self::COUNTED, $length);
    }

    /**
     * @param resource $socket
     */
    public static function untilClose($socket): self
    {
        return new self($socket, self::UNTIL_CLOSE);
    }

    /**
     * No body at all, as after a HEAD or a 204; the socket is released.
     *
     * @param resource $socket
     */
    public static function none($socket): self
    {
        fclose($socket);

        return new self(null, self::UNTIL_CLOSE);
    }

    public function pull(int $bytes): mixed
    {
        if ($this->done) {
            return Source::EOF;
        }

        $data = match ($this->framing) {
            self::CHUNKED => $this->chunk($bytes),
            self::COUNTED => $this->counted($bytes),
            default => $this->rest($bytes),
        };
        if (Source::EOF === $data) {
            $this->close();
        }

        return $data;
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->done = true;
    }

    private function chunk(int $bytes): string
    {
        if (0 === $this->remaining && !$this->startChunk()) {
            return Source::EOF;
        }

        $data = fread($this->socket, min($bytes, $this->remaining));
        if (false === $data || '' === $data) {
            return Source::EOF;
        }

        $this->remaining -= strlen($data);
        if (0 === $this->remaining) {
            fgets($this->socket);
        }

        return $data;
    }

    /**
     * Read the next chunk's size line; false at the terminating zero-size chunk, after its trailers.
     */
    private function startChunk(): bool
    {
        $line = fgets($this->socket);
        if (false === $line) {
            return false;
        }

        $size = hexdec(trim(explode(';', $line, 2)[0]));
        if (0 === $size) {
            while (false !== ($trailer = fgets($this->socket)) && '' !== trim($trailer)) {
            }

            return false;
        }

        $this->remaining = (int) $size;

        return true;
    }

    private function counted(int $bytes): string
    {
        if (0 === $this->remaining) {
            return Source::EOF;
        }

        $data = fread($this->socket, min($bytes, $this->remaining));
        if (false === $data || '' === $data) {
            return Source::EOF;
        }

        $this->remaining -= strlen($data);

        return $data;
    }

    private function rest(int $bytes): string
    {
        $data = fread($this->socket, $bytes);
        if (false === $data || ('' === $data && feof($this->socket))) {
            return Source::EOF;
        }

        return $data;
    }
}
