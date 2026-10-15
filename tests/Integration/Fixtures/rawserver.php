<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// A raw HTTP/1.1 server for the client tests: it reports what it received and answers with the
// framings a client has to handle. One connection at a time, started as: php rawserver.php <port>

$server = stream_socket_server('tcp://127.0.0.1:' . $argv[1]) ?: exit(1);

$readBody = function ($conn, array $headers): string {
    if (str_contains(strtolower($headers['transfer-encoding'] ?? ''), 'chunked')) {
        $body = '';
        while (0 < ($size = hexdec(trim((string) fgets($conn))))) {
            $chunk = '';
            while (strlen($chunk) < $size) {
                $chunk .= fread($conn, $size - strlen($chunk));
            }
            $body .= $chunk;
            fgets($conn);
        }

        return $body;
    }

    $body = '';
    while (strlen($body) < (int) ($headers['content-length'] ?? 0)) {
        $body .= fread($conn, (int) $headers['content-length'] - strlen($body));
    }

    return $body;
};

while ($conn = @stream_socket_accept($server, -1)) {
    [$method, $target] = explode(' ', trim((string) fgets($conn)));
    $headers = [];
    while ('' !== ($line = trim((string) fgets($conn)))) {
        [$name, $value] = explode(':', $line, 2);
        $headers[strtolower(trim($name))] = trim($value);
    }
    $body = $readBody($conn, $headers);

    $response = match ($target) {
        '/chunked' => "HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n6\r\nfirst \r\n7\r\nsecond \r\n5\r\nthird\r\n0\r\n\r\n",
        '/until-close' => "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nall of it",
        '/empty' => "HTTP/1.1 204 No Content\r\n\r\n",
        '/missing' => "HTTP/1.1 404 Not Found\r\nContent-Length: 9\r\n\r\nnot found",
        default => (function () use ($method, $target, $headers, $body) {
            $json = json_encode(['method' => $method, 'target' => $target, 'headers' => $headers, 'bodyLength' => strlen($body), 'bodyHash' => md5($body)]);

            return "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: " . strlen($json) . "\r\n\r\n" . $json;
        })(),
    };
    fwrite($conn, $response);
    fclose($conn);
}
