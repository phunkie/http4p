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
    use Phunkie\Http4p\Encoder\JsonEncoder;
    use Phunkie\Http4p\EntityEncoder;
    use Phunkie\Http4p\Headers;
    use Phunkie\Http4p\Response as ResponseClass;
    use Phunkie\Http4p\Status;

    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Http4p\Functions\response\createResponse;

    /**
     * Create a Response.
     *
     * This function accepts strings for the body and will convert them to Stream<F, Byte>
     * when Phunkie\Streams is integrated. For now, strings are passed through directly.
     *
     * When using `new Response()` directly, you should pass the proper Stream type.
     *
     * @param Status $status
     * @param Headers|null $headers
     * @param mixed $body String or Stream<F, Byte>
     * @return ResponseClass
     */
    function Response(Status $status, ?Headers $headers = null, mixed $body = ''): ResponseClass
    {
        // TODO: Convert string to Stream<F, Byte> when Streams is available
        return new ResponseClass($status, $headers ?? Headers(), $body);
    }

    /**
     * Create a 200 OK response.
     *
     * @template A
     * @param A $body
     * @param EntityEncoder|null $encoder
     * @return IO<Response>
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
     * @return IO<Response>
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
     * @return IO<Response>
     */
    function Accepted(mixed $body = null, ?EntityEncoder $encoder = null): IO
    {
        return createResponse(StatusAccepted(), $body, $encoder);
    }

    /**
     * Create a 204 No Content response.
     *
     * @return IO<Response>
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
     * @return IO<Response>
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
     * @return IO<Response>
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
     * @return IO<Response>
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
     * @return IO<Response>
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
     * @return IO<Response>
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

    use function Phunkie\Effect\Functions\io\io;

    // Import global Response function
    use function Response;

    /**
     * Helper to create a response with encoded body.
     *
     * @internal
     */
    function createResponse(Status $status, mixed $body, ?EntityEncoder $encoder = null): IO
    {
        return io(function () use ($status, $body, $encoder) {
            $encoder = $encoder ?? new JsonEncoder();

            if ($body === null) {
                return Response($status, Headers(), '');
            }

            $encodedBody = $encoder->encode($body);
            $headers = Headers(['content-type' => $encoder->contentType()]);

            return Response($status, $headers, $encodedBody);
        });
    }
}
