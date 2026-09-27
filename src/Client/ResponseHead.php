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

use Phunkie\Http4p\Headers;
use Phunkie\Http4p\Status;
use RuntimeException;

use function Phunkie\Http4p\Functions\Headers;

/**
 * The status and headers of a response, read before any of its body.
 */
final readonly class ResponseHead
{
    public function __construct(
        public Status $status,
        public Headers $headers,
    ) {
    }

    /**
     * From the lines PHP's http stream wrapper collects: a status line followed by header lines,
     * repeated once per redirect, of which the last response is the one that matters.
     *
     * @param list<string> $lines
     */
    public static function fromWrapperData(array $lines): self
    {
        $start = null;
        foreach ($lines as $index => $line) {
            if (str_starts_with($line, 'HTTP/')) {
                $start = $index;
            }
        }
        if (null === $start) {
            throw new RuntimeException('The response carries no HTTP status line.');
        }

        [, $code, $reason] = explode(' ', $lines[$start], 3) + [1 => '0', 2 => ''];
        $headers = [];
        foreach (array_slice($lines, $start + 1) as $line) {
            [$name, $value] = explode(':', $line, 2) + [1 => ''];
            $headers[strtolower(trim($name))] = trim($value);
        }

        return new self(new Status((int) $code, $reason), Headers($headers));
    }
}
