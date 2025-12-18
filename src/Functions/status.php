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

    use Phunkie\Http4p\Status;

    // 2xx Success
    function StatusOk(): Status
    {
        return new Status(200, 'OK');
    }

    function StatusCreated(): Status
    {
        return new Status(201, 'Created');
    }

    function StatusAccepted(): Status
    {
        return new Status(202, 'Accepted');
    }

    function StatusNoContent(): Status
    {
        return new Status(204, 'No Content');
    }

    // 3xx Redirection
    function StatusMovedPermanently(): Status
    {
        return new Status(301, 'Moved Permanently');
    }

    function StatusFound(): Status
    {
        return new Status(302, 'Found');
    }

    function StatusSeeOther(): Status
    {
        return new Status(303, 'See Other');
    }

    function StatusNotModified(): Status
    {
        return new Status(304, 'Not Modified');
    }

    // 4xx Client Errors
    function StatusBadRequest(): Status
    {
        return new Status(400, 'Bad Request');
    }

    function StatusUnauthorized(): Status
    {
        return new Status(401, 'Unauthorized');
    }

    function StatusForbidden(): Status
    {
        return new Status(403, 'Forbidden');
    }

    function StatusNotFound(): Status
    {
        return new Status(404, 'Not Found');
    }

    function StatusMethodNotAllowed(): Status
    {
        return new Status(405, 'Method Not Allowed');
    }

    function StatusConflict(): Status
    {
        return new Status(409, 'Conflict');
    }

    // 5xx Server Errors
    function StatusInternalServerError(): Status
    {
        return new Status(500, 'Internal Server Error');
    }

    function StatusNotImplemented(): Status
    {
        return new Status(501, 'Not Implemented');
    }

    function StatusServiceUnavailable(): Status
    {
        return new Status(503, 'Service Unavailable');
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
