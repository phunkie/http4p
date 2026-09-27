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
 * Where a server writes a response: the status, the headers, then the body one chunk at a time.
 */
interface Output
{
    public function status(int $code): void;

    public function header(string $name, string $value): void;

    /**
     * Write one chunk of the body and get it to the client before the next chunk is produced.
     */
    public function write(string $chunk): void;
}
