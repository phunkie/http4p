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
    use Phunkie\Http4p\Response;
    use Phunkie\Streams\Type\Stream;

    use function Phunkie\Effect\Functions\io\io;
    use function Phunkie\Http4p\Functions\decoding\json;

    /**
     * Decode the request body: as JSON by default, into an entity's fields when given its class,
     * or through any callable(string): mixed.
     *
     * The IO fails with a DecodeFailure when the body cannot be decoded; the router turns that into a 400.
     *
     * When decoding into an entity, the request's path parameters and $known fill the constructor
     * parameters of the same name ahead of the body, so a route can supply what the client must not.
     *
     * @param class-string|callable(string): mixed|null $decoder
     * @param array<string, mixed> $known values keyed by constructor parameter name
     * @return IO<mixed>
     */
    function decode(Request $request, string|callable|null $decoder = null, array $known = []): IO
    {
        $decoder = match (true) {
            null === $decoder => json(),
            is_string($decoder) => new EntityDecoder($decoder, $request->method, $known + $request->pathParams),
            default => $decoder,
        };

        return io(fn () => $decoder(implode('', $request->body->compile()->toArray())));
    }

    /**
     * Decode a body of newline-delimited JSON, one entity per line, as a Stream of that entity.
     *
     * Each line is checked against the entity's constructor the way a full request body is, with
     * the generated parameters expected as well since the rows come from a store, and becomes an
     * instance. Blank lines are skipped. A line that does not fit fails the stream with a
     * DecodeFailure when the stream reaches it, so nothing is decoded before it is needed.
     *
     * @param class-string $class
     * @return Stream of instances of $class
     */
    function decodeLines(Request|Response $message, string $class): Stream
    {
        $decoder = EntityDecoder::stored($class);

        return $message->body
            ->lines()
            ->filter(fn (string $line) => '' !== trim($line))
            ->map(fn (string $line) => new $class(...$decoder($line)));
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
