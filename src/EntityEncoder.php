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
 * Encodes values of type A into response bodies (Stream<F, Byte>).
 *
 * @template F The effect type
 * @template A The value type
 */
interface EntityEncoder
{
    /**
     * Encode a value into a response body.
     *
     * For now returns string until Stream is implemented.
     *
     * @param A $value
     * @return string The encoded body (will be Stream<F, Byte> later)
     */
    public function encode(mixed $value): string;

    /**
     * The content type this encoder produces.
     */
    public function contentType(): string;
}
