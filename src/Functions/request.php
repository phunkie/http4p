<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Functions {

    use Phunkie\Http4p\Method;
    use Phunkie\Http4p\Request as RequestClass;
    use Phunkie\Http4p\Headers;
    use Phunkie\Streams\Type\Stream;

    /**
     * Create a Request.
     *
     * @param Method $method
     * @param string $uri
     * @param Headers|null $headers
     * @param mixed $body
     * @return RequestClass
     */
    function Request(Method $method, string $uri, ?Headers $headers = null, mixed $body = null): RequestClass
    {
        $headers ??= Headers();
        $body ??= '';

        if ($body instanceof Stream) {
            return new RequestClass($method, $uri, $headers, $body);
        }

        return new RequestClass($method, $uri, $headers, \Stream($body));
    }
}

namespace Phunkie\Http4p\Functions\request {

    use Phunkie\Http4p\Request;

    // Request helper functions can go here if needed
}
