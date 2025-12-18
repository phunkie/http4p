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

    use Phunkie\Http4p\Status as StatusClass;

    /**
     * Create a Status.
     *
     * @param int $code
     * @param string $reason
     * @return StatusClass
     */
    function Status(int $code, string $reason): StatusClass
    {
        return new StatusClass($code, $reason);
    }

    // 2xx Success
    function StatusOk(): StatusClass
    {
        return new StatusClass(200, 'OK');
    }

    function StatusCreated(): StatusClass
    {
        return new StatusClass(201, 'Created');
    }

    function StatusAccepted(): StatusClass
    {
        return new StatusClass(202, 'Accepted');
    }

    function StatusNoContent(): StatusClass
    {
        return new StatusClass(204, 'No Content');
    }

    // 3xx Redirection
    function StatusMovedPermanently(): StatusClass
    {
        return new StatusClass(301, 'Moved Permanently');
    }

    function StatusFound(): StatusClass
    {
        return new StatusClass(302, 'Found');
    }

    function StatusSeeOther(): StatusClass
    {
        return new StatusClass(303, 'See Other');
    }

    function StatusNotModified(): StatusClass
    {
        return new StatusClass(304, 'Not Modified');
    }

    // 4xx Client Errors
    function StatusBadRequest(): StatusClass
    {
        return new StatusClass(400, 'Bad Request');
    }

    function StatusUnauthorized(): StatusClass
    {
        return new StatusClass(401, 'Unauthorized');
    }

    function StatusForbidden(): StatusClass
    {
        return new StatusClass(403, 'Forbidden');
    }

    function StatusNotFound(): StatusClass
    {
        return new StatusClass(404, 'Not Found');
    }

    function StatusMethodNotAllowed(): StatusClass
    {
        return new StatusClass(405, 'Method Not Allowed');
    }

    function StatusConflict(): StatusClass
    {
        return new StatusClass(409, 'Conflict');
    }

    // 5xx Server Errors
    function StatusInternalServerError(): StatusClass
    {
        return new StatusClass(500, 'Internal Server Error');
    }

    function StatusNotImplemented(): StatusClass
    {
        return new StatusClass(501, 'Not Implemented');
    }

    function StatusServiceUnavailable(): StatusClass
    {
        return new StatusClass(503, 'Service Unavailable');
    }
}

namespace Phunkie\Http4p\Functions\status {

    use Phunkie\Http4p\Status;

    function isSuccess(Status $status): bool
    {
        return $status->code >= 200 && $status->code < 300;
    }

    function isRedirect(Status $status): bool
    {
        return $status->code >= 300 && $status->code < 400;
    }

    function isClientError(Status $status): bool
    {
        return $status->code >= 400 && $status->code < 500;
    }

    function isServerError(Status $status): bool
    {
        return $status->code >= 500 && $status->code < 600;
    }
}
