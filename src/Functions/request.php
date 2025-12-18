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

    use Phunkie\Http4p\Method;
    use Phunkie\Http4p\Request;
    use Phunkie\Http4p\Headers;

    /**
     * Create a Request.
     *
     * @param Method $method
     * @param string $uri
     * @param Headers|null $headers
     * @param mixed $body
     * @return Request
     */
    function RequestOf(Method $method, string $uri, ?Headers $headers = null, mixed $body = null): Request
    {
        return new Request($method, $uri, $headers ?? EmptyHeaders(), $body);
    }
}

namespace Phunkie\Http4p\Functions\request {

    use Phunkie\Http4p\Request;

    // Request helper functions can go here if needed
}
