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

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
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
 * parameters marked #[Generated] are dropped. Values the route already knows, the request's path
 * parameters and anything the caller provides, fill the parameter of the same name ahead of the body.
 * On POST and PUT every parameter without a default that is not nullable is required; a PATCH may
 * carry any subset, as long as one field is known.
 *
 * Scalars must match the declared type as JSON gives them, with no coercion. A parameter typed with
 * a backed enum, a date or any class with a single-argument constructor is built from the scalar,
 * and an InvalidArgumentException thrown by that constructor becomes the field's error.
 */
final class EntityDecoder
{
    private const GENERATED = 'Phunkie\Phetch\Attributes\Generated';

    private bool $includingGenerated = false;

    /** @var list<ReflectionParameter>|null */
    private ?array $parameters = null;

    /**
     * @param class-string $class
     * @param array<string, mixed> $known values the route already has, keyed by parameter name
     */
    public function __construct(
        private string $class,
        private Method $method,
        private array $known = [],
    ) {
    }

    /**
     * A decoder for an entity as a store holds it: every parameter without a default is required,
     * the generated ones included, since they were produced when the row was written.
     *
     * @param class-string $class
     */
    public static function stored(string $class): self
    {
        $decoder = new self($class, Method::PUT);
        $decoder->includingGenerated = true;

        return $decoder;
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
            if (! $this->includingGenerated && $this->isGenerated($param)) {
                continue;
            }

            $name = $param->getName();
            if (array_key_exists($name, $this->known)) {
                $fields[$name] = $this->known[$name];

                continue;
            }

            $key = $this->keyFor($param, $json);
            if (null === $key) {
                if ($this->isRequired($param)) {
                    $errors[$name] = 'missing';
                }

                continue;
            }

            $elementType = $this->elementTypeOf($param);
            if (null !== $elementType && is_array($json[$key])) {
                [$elements, $elementErrors] = $this->elements($elementType, $name, $json[$key]);
                $errors += $elementErrors;
                if ([] === $elementErrors) {
                    $fields[$name] = $elements;
                }

                continue;
            }

            try {
                $fields[$name] = $this->valueFor($param, $json[$key]);
            } catch (InvalidArgumentException $e) {
                $errors[$name] = $e->getMessage();
            }
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
        return $this->parameters ??= (new ReflectionClass($this->class))->getConstructor()?->getParameters() ?? [];
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

    /**
     * The value to hand to the parameter, or an InvalidArgumentException saying why the JSON value will not do.
     */
    private function valueFor(ReflectionParameter $param, mixed $value): mixed
    {
        $type = $param->getType();
        if (null === $type) {
            return $value;
        }

        if (null === $value) {
            if ($type->allowsNull()) {
                return null;
            }

            throw new InvalidArgumentException(sprintf('expected %s, got null', $type));
        }

        $names = $this->typeNames($type);
        foreach ($names as $name) {
            if ($this->accepts($name, $value)) {
                return $value;
            }
        }

        foreach ($names as $name) {
            if (is_scalar($value) && class_exists($name)) {
                return $this->construct($name, $value);
            }
        }

        throw new InvalidArgumentException(sprintf('expected %s, got %s', implode('|', $names), get_debug_type($value)));
    }

    /**
     * Every element of a list parameter built from the element type its docblock declares, with one error per bad element.
     *
     * @param array<mixed> $list
     * @return array{0: list<mixed>, 1: array<string, string>}
     */
    private function elements(string $type, string $name, array $list): array
    {
        if (! array_is_list($list)) {
            return [[], [$name => 'expected a list']];
        }

        $elements = [];
        $errors = [];
        foreach ($list as $index => $value) {
            try {
                $elements[] = $this->element($type, $value);
            } catch (InvalidArgumentException $e) {
                $errors[$name.'.'.$index] = $e->getMessage();
            }
        }

        return [$elements, $errors];
    }

    private function element(string $type, mixed $value): mixed
    {
        if ($this->accepts($type, $value)) {
            return $value;
        }

        if (is_scalar($value) && class_exists($type)) {
            return $this->construct($type, $value);
        }

        throw new InvalidArgumentException(sprintf('expected %s, got %s', $type, get_debug_type($value)));
    }

    /**
     * The element type of an array parameter, read from "@param list<T> $name", "array<T>" or "T[]" in the constructor docblock.
     */
    private function elementTypeOf(ReflectionParameter $param): ?string
    {
        $type = $param->getType();
        if (! $type instanceof ReflectionNamedType || ! in_array($type->getName(), ['array', 'iterable'], true)) {
            return null;
        }

        $doc = $param->getDeclaringFunction()->getDocComment();
        if (false === $doc) {
            return null;
        }

        $name = preg_quote($param->getName(), '/');
        if (! preg_match('/@param\s+(?:(?:list|array)<([^>]+)>|([\w\\\\]+)\[\])\s+\$'.$name.'\b/', $doc, $match)) {
            return null;
        }

        $element = ltrim('' !== $match[1] ? $match[1] : $match[2], '\\');
        $namespaced = $param->getDeclaringClass()?->getNamespaceName().'\\'.$element;

        return class_exists($element) || ! class_exists($namespaced) ? $element : $namespaced;
    }

    /**
     * @param class-string $class
     */
    private function construct(string $class, int|float|string|bool $value): object
    {
        if (is_subclass_of($class, BackedEnum::class)) {
            return $class::tryFrom($value) ?? throw new InvalidArgumentException(sprintf(
                'expected one of %s, got %s',
                implode(', ', array_map(fn (BackedEnum $case) => $case->value, $class::cases())),
                json_encode($value),
            ));
        }

        if (is_a($class, DateTimeInterface::class, true)) {
            try {
                return new ($class === DateTimeInterface::class ? DateTimeImmutable::class : $class)((string) $value);
            } catch (Exception) {
                throw new InvalidArgumentException(sprintf('expected a date, got %s', json_encode($value)));
            }
        }

        $expected = (new ReflectionClass($class))->getConstructor()?->getParameters()[0]?->getType();
        if ($expected instanceof ReflectionNamedType && $expected->isBuiltin() && ! $this->accepts($expected->getName(), $value)) {
            throw new InvalidArgumentException(sprintf('expected %s, got %s', $expected->getName(), get_debug_type($value)));
        }

        return new $class($value);
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
