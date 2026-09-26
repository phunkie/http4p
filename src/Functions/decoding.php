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
    use Phunkie\Http4p\Request;

    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Http4p\Functions\decoding\json;

    /**
     * Decode the request body, as JSON unless a decoder is given.
     *
     * The IO fails with a DecodeFailure when the body cannot be decoded; the router turns that into a 400.
     *
     * @param callable(string): mixed|null $decoder
     * @return IO<mixed>
     */
    function decode(Request $request, ?callable $decoder = null): IO
    {
        $decoder ??= json();

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
                throw new DecodeFailure(sprintf('Body is not valid JSON: %s.', $e->getMessage()), 0, $e);
            }
        };
    }

    /**
     * A decoder for a JSON object that keeps only the named fields, or every field when none is named.
     *
     * @return callable(string): array<string, mixed>
     */
    function jsonObject(string ...$fields): callable
    {
        $json = json();

        return function (string $body) use ($json, $fields): array {
            $data = $json($body);
            if (! is_array($data) || ([] !== $data && array_is_list($data))) {
                throw new DecodeFailure('Body must be a JSON object.');
            }

            if ([] === $fields) {
                return $data;
            }

            $accepted = array_intersect_key($data, array_flip($fields));
            if ([] === $accepted) {
                throw new DecodeFailure(sprintf('Body must contain at least one of: %s.', implode(', ', $fields)));
            }

            return $accepted;
        };
    }
}
