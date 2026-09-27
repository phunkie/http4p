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

/**
 * Writes the response through PHP's SAPI: php-fpm, the built-in server, FrankenPHP, RoadRunner.
 *
 * Every chunk is flushed through any output buffer and the SAPI as soon as it is written, so a
 * streamed body reaches the client as it is produced. No Content-Length is added; without one the
 * SAPI sends the body chunked.
 */
final class SapiOutput implements Output
{
    public function status(int $code): void
    {
        http_response_code($code);
    }

    public function header(string $name, string $value): void
    {
        header(sprintf('%s: %s', $name, $value));
    }

    public function write(string $chunk): void
    {
        echo $chunk;
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
