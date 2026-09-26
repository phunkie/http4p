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

    use Phunkie\Http4p\Headers;
    use Phunkie\Http4p\Response as ResponseClass;
    use Phunkie\Http4p\Status;
    use Phunkie\Streams\Type\Stream;

    /**
     * Create a Response.
     *
     * @param Status $status
     * @param Headers|null $headers
     * @param mixed $body String or Stream<F, Byte>
     * @return ResponseClass
     */
    function Response(Status $status, ?Headers $headers = null, mixed $body = ''): ResponseClass
    {
        $headers ??= Headers();

        if ($body instanceof Stream) {
            return new ResponseClass($status, $headers, $body);
        }

        return new ResponseClass($status, $headers, \Stream($body));
    }
}

namespace Phunkie\Http4p\Functions\response {

    use Phunkie\Streams\Type\Stream;
    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Encoder\JsonEncoder;
    use Phunkie\Http4p\EntityEncoder;
    use Phunkie\Http4p\Headers;
    use Phunkie\Http4p\Response as ResponseClass;
    use Phunkie\Http4p\Status;

    use function Phunkie\Http4p\Functions\StatusAccepted;
    use function Phunkie\Http4p\Functions\StatusBadRequest;
    use function Phunkie\Http4p\Functions\StatusCreated;
    use function Phunkie\Http4p\Functions\StatusForbidden;
    use function Phunkie\Http4p\Functions\StatusInternalServerError;
    use function Phunkie\Http4p\Functions\StatusNoContent;
    use function Phunkie\Http4p\Functions\StatusNotFound;
    use function Phunkie\Http4p\Functions\StatusOk;
    use function Phunkie\Http4p\Functions\StatusUnauthorized;

    /**
     * Create a 200 OK response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function Ok(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusOk(), $body, $encoder);
    }

    /**
     * Create a 201 Created response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function Created(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusCreated(), $body, $encoder);
    }

    /**
     * Create a 202 Accepted response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function Accepted(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusAccepted(), $body, $encoder);
    }

    /**
     * Create a 204 No Content response.
     *
     * @return IO<ResponseClass>
     */
    function NoContent(): IO
    {
        return createResponse(StatusNoContent(), null);
    }

    /**
     * Create a 400 Bad Request response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function BadRequest(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusBadRequest(), $body, $encoder);
    }

    /**
     * Create a 401 Unauthorized response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function Unauthorized(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusUnauthorized(), $body, $encoder);
    }

    /**
     * Create a 403 Forbidden response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function Forbidden(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusForbidden(), $body, $encoder);
    }

    /**
     * Create a 404 Not Found response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function NotFound(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusNotFound(), $body, $encoder);
    }

    /**
     * Create a 500 Internal Server Error response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<ResponseClass>
     */
    function InternalServerError(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusInternalServerError(), $body, $encoder);
    }
}

namespace Phunkie\Http4p\Functions\response {

    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Encoder\JsonEncoder;
    use Phunkie\Http4p\EntityEncoder;
    use Phunkie\Http4p\Headers;
    use Phunkie\Http4p\Response as ResponseClass;
    use Phunkie\Http4p\Status;
    use Phunkie\Streams\Type\Stream;

    use function Phunkie\Effect\Functions\io\io;

    use function Phunkie\Http4p\Functions\Headers;
    use function Phunkie\Http4p\Functions\Response;

    /**
     * Helper to create a response with encoded body.
     *
     * @internal
     */
    function createResponse(Status $status, mixed $body, ?EntityEncoder $encoder = null): IO
    {
        return io(function () use ($status, $body, $encoder) {
            $encoder ??= new JsonEncoder();

            if ($body === null) {
                return Response($status, Headers(), '');
            }

            if ($body instanceof Stream) {
                return Response($status, Headers(), $body);
            }

            $encodedBody = $encoder->encode($body);
            $headers = Headers(['content-type' => $encoder->contentType()]);

            return Response($status, $headers, $encodedBody);
        });
    }
}
