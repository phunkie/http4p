<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Encoder;

use Phunkie\Http4p\EntityEncoder;

/**
 * Default JSON encoder for response bodies.
 *
 * @implements EntityEncoder<mixed, mixed>
 */
final class JsonEncoder implements EntityEncoder
{
    public function encode(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}
