<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Client;

use InvalidArgumentException;
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Request;
use Phunkie\Http4p\Response;
use RuntimeException;

use function Phunkie\Effect\Functions\io\io;

/**
 * Sends a request over its own HTTP/1.1 connection, plain or TLS, and reads the response from it.
 *
 * The request body streams out: a pure body (built from a string) is sent with its Content-Length,
 * a resource-backed body is sent with chunked transfer encoding, one chunk written as it is pulled.
 * The response head is read before the body, which is left on the socket to be pulled as a Stream.
 * Redirects are not followed; a 3xx comes back like any other status.
 */
final class SocketTransport
{
    public function __construct(private float $timeout = 30.0)
    {
    }

    public function send(Request $request): Response
    {
        $url = $this->parsed($request->uri);
        $socket = $this->connect($url);
        $chunks = \Pure === $request->body->getEffect() ? $request->body->compile()->toArray() : null;

        $this->writeAll($socket, $this->head($request, $url, $chunks));
        $this->writeBody($socket, $request, $chunks);

        $head = ResponseHead::fromWrapperData($this->headLines($socket));

        return new Response($head->status, $head->headers, \Stream($this->body($socket, $request->method, $head)));
    }

    /**
     * @return array{scheme: string, host: string, port: int, target: string}
     */
    private function parsed(string $uri): array
    {
        $parts = parse_url($uri);
        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || !isset($parts['host'])) {
            throw new InvalidArgumentException(sprintf('send() needs an absolute http or https URL, got "%s".', $uri));
        }

        $target = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        return ['scheme' => $scheme, 'host' => $parts['host'], 'port' => $parts['port'] ?? ('https' === $scheme ? 443 : 80), 'target' => '' === $target ? '/' : $target];
    }

    /**
     * @param array{scheme: string, host: string, port: int, target: string} $url
     * @return resource
     */
    private function connect(array $url)
    {
        $address = sprintf('%s://%s:%d', 'https' === $url['scheme'] ? 'ssl' : 'tcp', $url['host'], $url['port']);
        $socket = @stream_socket_client($address, $errno, $error, $this->timeout);
        if (false === $socket) {
            throw new RuntimeException(sprintf('Could not connect to %s: %s.', $address, $error ?: 'error ' . $errno));
        }
        stream_set_timeout($socket, (int) $this->timeout, (int) (($this->timeout - (int) $this->timeout) * 1000000));

        return $socket;
    }

    /**
     * @param array{scheme: string, host: string, port: int, target: string} $url
     * @param list<string>|null $chunks the body when its length is known up front
     */
    private function head(Request $request, array $url, ?array $chunks): string
    {
        $defaultPort = 'https' === $url['scheme'] ? 443 : 80;
        $lines = [
            sprintf('%s %s HTTP/1.1', $request->method->value, $url['target']),
            'Host: ' . $url['host'] . ($url['port'] === $defaultPort ? '' : ':' . $url['port']),
            'Connection: close',
        ];
        foreach ($request->headers->toArray() as $name => $value) {
            $lines[] = sprintf('%s: %s', $name, $value);
        }

        $length = null === $chunks ? null : array_sum(array_map('strlen', $chunks));
        if (null === $length) {
            $lines[] = 'Transfer-Encoding: chunked';
        } elseif ($length > 0) {
            $lines[] = 'Content-Length: ' . $length;
        }

        return implode("\r\n", $lines) . "\r\n\r\n";
    }

    /**
     * @param resource $socket
     * @param list<string>|null $chunks
     */
    private function writeBody($socket, Request $request, ?array $chunks): void
    {
        if (null !== $chunks) {
            $this->writeAll($socket, implode('', $chunks));

            return;
        }

        $request->body
            ->evalTap(fn (string $chunk) => io(fn () => '' === $chunk ? null : $this->writeAll($socket, dechex(strlen($chunk)) . "\r\n" . $chunk . "\r\n")))
            ->compile()
            ->drain()
            ->unsafeRun();
        $this->writeAll($socket, "0\r\n\r\n");
    }

    /**
     * @param resource $socket
     */
    private function writeAll($socket, string $data): void
    {
        while ('' !== $data) {
            $written = fwrite($socket, $data);
            if (false === $written || 0 === $written) {
                throw new RuntimeException('The connection was closed while sending the request.');
            }
            $data = substr($data, $written);
        }
    }

    /**
     * The status line and header lines, up to the blank line that ends the head.
     *
     * @param resource $socket
     * @return list<string>
     */
    private function headLines($socket): array
    {
        $lines = [];
        while (false !== ($line = fgets($socket))) {
            $line = rtrim($line, "\r\n");
            if ('' === $line && [] !== $lines) {
                return $lines;
            }
            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        throw new RuntimeException('The connection was closed before a response arrived.');
    }

    /**
     * @param resource $socket
     */
    private function body($socket, Method $method, ResponseHead $head): ResponseBody
    {
        if (Method::HEAD === $method || in_array($head->status->code, [204, 304], true)) {
            return ResponseBody::none($socket);
        }

        if (str_contains(strtolower($head->headers->get('transfer-encoding') ?? ''), 'chunked')) {
            return ResponseBody::chunked($socket);
        }

        $length = $head->headers->get('content-length');

        return null === $length ? ResponseBody::untilClose($socket) : ResponseBody::ofLength($socket, (int) $length);
    }
}
