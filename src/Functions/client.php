<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Functions\client {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Client\ResponseHead;
    use Phunkie\Http4p\Request;
    use Phunkie\Http4p\Response;
    use Phunkie\Streams\IO\Network\HttpRequest;

    use function Phunkie\Effect\Functions\io\io;

    /**
     * Send a request to another service and describe its response.
     *
     * The request goes out and the status and headers are read when the IO runs. The body is a
     * Stream pulled from the connection chunk by chunk as it is compiled, so a large response is
     * consumed in constant memory: compose it with lines(), decodeLines() or any stream operation.
     *
     * @param Request $request its uri absolute, http:// or https://
     * @param float $timeout seconds to wait for the connection and each read
     * @return IO<Response>
     */
    function send(Request $request, float $timeout = 30.0): IO
    {
        return io(function () use ($request, $timeout) {
            $body = implode('', $request->body->compile()->toArray());
            $http = new HttpRequest($request->uri, $request->method->value, $request->headers->toArray(), '' === $body ? null : $body, $timeout);
            $http->execute();
            $head = ResponseHead::fromWrapperData($http->getResponseHeaders());

            return new Response($head->status, $head->headers, \Stream($http));
        });
    }
}
