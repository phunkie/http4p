<?php

declare(strict_types=1);

/*
 * This file is part of Phunkie PHPStan, type extensions for Phunkie.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phunkie\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * A configurable extension for methods that transform generic type parameters.
 *
 * This extension handles common patterns in functional programming where
 * methods on container types (F<A>) return new containers with transformed
 * type parameters.
 *
 * Supported patterns:
 * - 'wrap': F<A>.method() → F<Wrapper<Fixed, A>> (e.g., attempt → Validation<Throwable, A>)
 * - 'map': F<A>.method(A → B) → F<B> (e.g., map, flatMap)
 * - 'pair': F<A>.method(A → B) → F<Pair<A, B>> (e.g., zipWith)
 * - 'preserve': F<A>.method() → F<A> (e.g., methods that don't change the type)
 *
 * Configuration in extension.neon:
 * ```
 * parameters:
 *     phunkie:
 *         genericMethods:
 *             - class: Phunkie\Effect\IO\IO
 *               method: attempt
 *               pattern: wrap
 *               wrapper: Phunkie\Validation\Validation
 *               fixedTypes: [Throwable]
 * ```
 */
class GenericMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @var string */
    private string $targetClass;

    /** @var array<string, array{pattern: string, wrapper?: string, fixedTypes?: array<string>, pairClass?: string}> */
    private array $methodConfigs;

    /**
     * @param string $targetClass The class this extension applies to
     * @param array<array{method: string, pattern: string, wrapper?: string, fixedTypes?: array<string>, pairClass?: string}> $methods
     */
    public function __construct(string $targetClass, array $methods)
    {
        $this->targetClass = $targetClass;
        $this->methodConfigs = [];

        foreach ($methods as $config) {
            $this->methodConfigs[$config['method']] = $config;
        }
    }

    public function getClass(): string
    {
        return $this->targetClass;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return isset($this->methodConfigs[$methodReflection->getName()]);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $methodName = $methodReflection->getName();
        $config = $this->methodConfigs[$methodName] ?? null;

        if ($config === null) {
            return null;
        }

        $calledOnType = $scope->getType($methodCall->var);

        // Get the container's type parameter (A from F<A>)
        $innerType = $this->getInnerType($calledOnType);

        return match ($config['pattern']) {
            'wrap' => $this->handleWrap($calledOnType, $innerType, $config),
            'map' => $this->handleMap($calledOnType, $methodCall, $scope),
            'pair' => $this->handlePair($calledOnType, $innerType, $methodCall, $scope, $config),
            'flatMap' => $this->handleFlatMap($methodCall, $scope),
            'preserve' => $calledOnType,
            default => null,
        };
    }

    /**
     * Handle flatMap pattern: F<A>.method(A → F<B>) → F<B>
     * The return type is simply the return type of the callback.
     */
    private function handleFlatMap(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 1) {
            return null;
        }

        $callableType = $scope->getType($args[0]->value);
        $acceptors = $callableType->getCallableParametersAcceptors($scope);

        if (count($acceptors) === 0) {
            return null;
        }

        return ParametersAcceptorSelector::selectFromArgs(
            $scope,
            [],
            $acceptors
        )->getReturnType();
    }

    private function getInnerType(Type $containerType): Type
    {
        if ($containerType instanceof GenericObjectType) {
            $types = $containerType->getTypes();
            if (count($types) >= 1) {
                return $types[0];
            }
        }

        return new MixedType();
    }

    /**
     * Handle wrap pattern: F<A> → F<Wrapper<Fixed..., A>>
     */
    private function handleWrap(Type $containerType, Type $innerType, array $config): ?Type
    {
        $wrapperClass = $config['wrapper'] ?? null;
        if ($wrapperClass === null) {
            return null;
        }

        $fixedTypes = [];
        foreach ($config['fixedTypes'] ?? [] as $fixedType) {
            $fixedTypes[] = new ObjectType($fixedType);
        }

        // Create Wrapper<Fixed..., A>
        $wrappedType = new GenericObjectType($wrapperClass, [...$fixedTypes, $innerType]);

        // Return F<Wrapper<Fixed..., A>>
        if ($containerType instanceof GenericObjectType) {
            return new GenericObjectType($containerType->getClassName(), [$wrappedType]);
        }

        return null;
    }

    /**
     * Handle map pattern: F<A>.method(A → B) → F<B>
     */
    private function handleMap(Type $containerType, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (count($args) < 1) {
            return null;
        }

        $callableType = $scope->getType($args[0]->value);
        $acceptors = $callableType->getCallableParametersAcceptors($scope);

        if (count($acceptors) === 0) {
            return null;
        }

        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            [],
            $acceptors
        )->getReturnType();

        if ($containerType instanceof GenericObjectType) {
            $types = $containerType->getTypes();
            if (count($types) > 0) {
                $types[count($types) - 1] = $returnType;
                return new GenericObjectType($containerType->getClassName(), $types);
            }
            return new GenericObjectType($containerType->getClassName(), [$returnType]);
        }

        return null;
    }

    /**
     * Handle pair pattern: F<A>.method(A → B) → F<Pair<A, B>>
     */
    private function handlePair(
        Type $containerType,
        Type $innerType,
        MethodCall $methodCall,
        Scope $scope,
        array $config
    ): ?Type {
        $pairClass = $config['pairClass'] ?? 'Phunkie\\Types\\Pair';

        $args = $methodCall->getArgs();
        if (count($args) < 1) {
            return null;
        }

        $callableType = $scope->getType($args[0]->value);
        $acceptors = $callableType->getCallableParametersAcceptors($scope);

        if (count($acceptors) === 0) {
            return null;
        }

        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            [],
            $acceptors
        )->getReturnType();

        // Create Pair<A, B>
        $pairType = new GenericObjectType($pairClass, [$innerType, $returnType]);

        if ($containerType instanceof GenericObjectType) {
            return new GenericObjectType($containerType->getClassName(), [$pairType]);
        }

        return null;
    }
}
