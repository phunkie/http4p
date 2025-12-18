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

    use Phunkie\Http4p\Headers as HeadersClass;
    use Phunkie\Types\ImmMap;

    /**
     * Create Headers from array or empty.
     *
     * @param array<string, string> $headers
     */
    function Headers(array $headers = []): HeadersClass
    {
        return new HeadersClass(ImmMap(array_change_key_case($headers, CASE_LOWER)));
    }
}

namespace Phunkie\Http4p\Functions\headers {

    use Phunkie\Http4p\Headers;

    function get(Headers $headers, string $name): ?string
    {
        return $headers->get($name);
    }

    function put(Headers $headers, string $name, string $value): Headers
    {
        return $headers->put($name, $value);
    }

    function remove(Headers $headers, string $name): Headers
    {
        return $headers->remove($name);
    }
}
