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
    use Phunkie\Http4p\Headers;
    use Phunkie\Streams\IO\Read;

    use function Phunkie\Effect\Functions\io\io;

    /**
     * Create a Response that streams a file.
     *
     * @param string $path Path to the file
     * @return IO<Response>
     */
    function FileResponse(string $path): IO
    {
        return io(function() use ($path) {
            if (!file_exists($path)) {
                return NotFound(['error' => "File not found: $path"]);
            }

            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $size = filesize($path);

            $headers = Headers([
                'Content-Type' => $mime,
                'Content-Length' => (string)$size
            ]);

            return io(fn() => Response(StatusOk(), $headers, Stream(new Read($path))));
        })->flatMap(fn($io) => $io);
    }
}
