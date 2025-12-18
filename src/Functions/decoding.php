<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Request;

    /**
     * Decode request body. Defaults to JSON.
     *
     * @param Request $request
     * @param callable(string): mixed|null $decoder
     * @return IO<mixed>
     */
    function decode(Request $request, ?callable $decoder = null): IO
    {
        $decoder = $decoder ?? fn($s) => json_decode($s, true);

        return $request->body->readAll()->map($decoder);
    }
}
