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
 * HTTP status code.
 *
 * @psalm-immutable
 */
final readonly class Status
{
    public function __construct(
        public int $code,
        public string $reason
    ) {}
}

