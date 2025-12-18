<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p;

use Phunkie\Types\ImmMap;

use function Phunkie\Functions\immmap\ImmMap;

/**
 * HTTP headers as an immutable map.
 *
 * @psalm-immutable
 */
final readonly class Headers
{
    /** @param ImmMap<string, string> $headers */
    public function __construct(
        public ImmMap $headers
    ) {}

    public function get(string $name): ?string
    {
        $normalized = strtolower($name);

        return $this->headers->get($normalized)->getOrElse(null);
    }

    public function put(string $name, string $value): self
    {
        $normalized = strtolower($name);

        return new self($this->headers->plus($normalized, $value));
    }

    public function remove(string $name): self
    {
        $normalized = strtolower($name);

        return new self($this->headers->minus($normalized));
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->headers->iterator() as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }
}
