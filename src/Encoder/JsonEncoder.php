<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Encoder;

use BackedEnum;
use DateTimeInterface;
use JsonSerializable;
use Phunkie\Http4p\EntityEncoder;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Default JSON encoder for response bodies.
 *
 * Dates render in ISO 8601, backed enums by value, and a value object, an object whose constructor
 * takes one scalar parameter and which exposes one public property, by that property, however deep.
 * A JsonSerializable renders as it says.
 *
 * @implements EntityEncoder<mixed, mixed>
 */
final class JsonEncoder implements EntityEncoder
{
    /** @var array<class-string, bool> */
    private array $valueObjectClasses = [];

    public function encode(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($this->normalized($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function contentType(): string
    {
        return 'application/json';
    }

    private function normalized(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof JsonSerializable => $this->normalized($value->jsonSerialize()),
            is_array($value) => array_map(fn ($item) => $this->normalized($item), $value),
            is_object($value) && $this->isValueObject($value) => $this->normalized(current(get_object_vars($value))),
            is_object($value) => array_map(fn ($item) => $this->normalized($item), get_object_vars($value)),
            default => $value,
        };
    }

    private function isValueObject(object $value): bool
    {
        return $this->valueObjectClasses[$value::class] ??= $this->wrapsOneScalar($value) && 1 === count(get_object_vars($value));
    }

    private function wrapsOneScalar(object $value): bool
    {
        $parameters = (new ReflectionClass($value))->getConstructor()?->getParameters() ?? [];
        if (1 !== count($parameters)) {
            return false;
        }

        $type = $parameters[0]->getType();

        return $type instanceof ReflectionNamedType && in_array($type->getName(), ['int', 'float', 'string', 'bool'], true);
    }
}
