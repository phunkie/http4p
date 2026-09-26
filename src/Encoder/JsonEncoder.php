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

use BackedEnum;
use DateTimeInterface;
use JsonSerializable;
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

        return json_encode($this->normalized($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The value with every date replaced by its ISO 8601 form and every backed enum by its value, however deep.
     */
    private function normalized(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof JsonSerializable => $this->normalized($value->jsonSerialize()),
            is_array($value) => array_map(fn ($item) => $this->normalized($item), $value),
            is_object($value) => array_map(fn ($item) => $this->normalized($item), get_object_vars($value)),
            default => $value,
        };
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}
