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

    use Phunkie\Effect\IO\IO;
    use Phunkie\Http4p\Decoder\EntityDecoder;
    use Phunkie\Http4p\Request;

    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Http4p\Functions\decoding\json;

    /**
     * Decode the request body: as JSON by default, into an entity's fields when given its class,
     * or through any callable(string): mixed.
     *
     * The IO fails with a DecodeFailure when the body cannot be decoded; the router turns that into a 400.
     *
     * @param class-string|callable(string): mixed|null $decoder
     * @return IO<mixed>
     */
    function decode(Request $request, string|callable|null $decoder = null): IO
    {
        $decoder = match (true) {
            null === $decoder => json(),
            is_string($decoder) => new EntityDecoder($decoder, $request->method),
            default => $decoder,
        };

        return io(fn () => $decoder(implode('', $request->body->compile()->toArray())));
    }
}

namespace Phunkie\Http4p\Functions\decoding {

    use JsonException;
    use Phunkie\Http4p\DecodeFailure;

    /**
     * A decoder for any JSON value.
     *
     * @return callable(string): mixed
     */
    function json(): callable
    {
        return function (string $body): mixed {
            try {
                return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new DecodeFailure(sprintf('Body is not valid JSON: %s.', $e->getMessage()), [], $e);
            }
        };
    }
}
