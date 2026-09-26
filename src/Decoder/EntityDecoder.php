<?php

/*
 * This file is part of Phunkie Http4p.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\Http4p\Decoder;

use Phunkie\Http4p\DecodeFailure;
use Phunkie\Http4p\Method;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

use function Phunkie\Http4p\Functions\decoding\json;

/**
 * Decodes a JSON object into the fields of an entity, keyed by constructor parameter name.
 *
 * A key matches a parameter by its exact name or by its snake_case form. Unknown keys and
 * parameters marked #[Generated] are dropped. On POST and PUT every parameter without a default
 * that is not nullable is required; a PATCH may carry any subset, as long as one field is known.
 * Values must match the declared parameter types as JSON gives them, with no coercion.
 */
final class EntityDecoder
{
    private const GENERATED = 'Phunkie\Phetch\Attributes\Generated';

    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class,
        private Method $method,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $body): array
    {
        $json = json()($body);
        if (! is_array($json) || ([] !== $json && array_is_list($json))) {
            throw new DecodeFailure('Body must be a JSON object.');
        }

        return $this->fields($json);
    }

    /**
     * @param array<string, mixed> $json
     * @return array<string, mixed>
     */
    private function fields(array $json): array
    {
        $fields = [];
        $errors = [];
        foreach ($this->parameters() as $param) {
            if ($this->isGenerated($param)) {
                continue;
            }

            $key = $this->keyFor($param, $json);
            if (null === $key) {
                if ($this->isRequired($param)) {
                    $errors[$param->getName()] = 'missing';
                }

                continue;
            }

            $mismatch = $this->typeMismatch($param, $json[$key]);
            if (null !== $mismatch) {
                $errors[$param->getName()] = $mismatch;

                continue;
            }

            $fields[$param->getName()] = $json[$key];
        }

        if ([] !== $errors) {
            throw new DecodeFailure(sprintf('Body does not describe %s.', $this->shortName()), $errors);
        }

        if ([] === $fields) {
            throw new DecodeFailure(sprintf('Body carries no field of %s.', $this->shortName()));
        }

        return $fields;
    }

    /**
     * @return list<ReflectionParameter>
     */
    private function parameters(): array
    {
        return (new ReflectionClass($this->class))->getConstructor()?->getParameters() ?? [];
    }

    private function isGenerated(ReflectionParameter $param): bool
    {
        return [] !== $param->getAttributes(self::GENERATED);
    }

    private function isRequired(ReflectionParameter $param): bool
    {
        if (Method::POST !== $this->method && Method::PUT !== $this->method) {
            return false;
        }

        return ! $param->isDefaultValueAvailable() && ! ($param->getType()?->allowsNull() ?? true);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function keyFor(ReflectionParameter $param, array $json): ?string
    {
        foreach ([$param->getName(), $this->snakeCase($param->getName())] as $candidate) {
            if (array_key_exists($candidate, $json)) {
                return $candidate;
            }
        }

        return null;
    }

    private function typeMismatch(ReflectionParameter $param, mixed $value): ?string
    {
        $type = $param->getType();
        if (null === $type) {
            return null;
        }

        if (null === $value) {
            return $type->allowsNull() ? null : sprintf('expected %s, got null', $type);
        }

        $names = $this->typeNames($type);
        foreach ($names as $name) {
            if ($this->accepts($name, $value)) {
                return null;
            }
        }

        return sprintf('expected %s, got %s', implode('|', $names), get_debug_type($value));
    }

    /**
     * @return list<string>
     */
    private function typeNames(ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }

        if ($type instanceof ReflectionUnionType) {
            return array_map(fn (ReflectionNamedType $member) => $member->getName(), $type->getTypes());
        }

        return [(string) $type];
    }

    private function accepts(string $type, mixed $value): bool
    {
        return match ($type) {
            'int' => is_int($value),
            'float' => is_int($value) || is_float($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'array', 'iterable' => is_array($value),
            'mixed' => true,
            default => is_array($value) && class_exists($type),
        };
    }

    private function snakeCase(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    private function shortName(): string
    {
        return (new ReflectionClass($this->class))->getShortName();
    }
}
