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
    use Phunkie\Http4p\Client\SocketTransport;
    use Phunkie\Http4p\Request;

    use function Phunkie\Effect\Functions\io\io;

    /**
     * Send a request to another service and describe its response.
     *
     * The request goes out over its own HTTP/1.1 connection when the IO runs: a body built from
     * a string is sent with its Content-Length, a resource-backed body streams out chunked, one
     * chunk written as it is pulled. The status and headers are read then; the body is a Stream
     * pulled from the connection chunk by chunk as it is compiled, so a large response is
     * consumed in constant memory: compose it with lines(), decodeLines() or any stream operation.
     * Redirects are not followed.
     *
     * @param Request $request its uri absolute, http:// or https://
     * @param float $timeout seconds to wait for the connection and for each read
     * @return IO<\Phunkie\Http4p\Response>
     */
    function send(Request $request, float $timeout = 30.0): IO
    {
        return io(fn () => (new SocketTransport($timeout))->send($request));
    }
}
