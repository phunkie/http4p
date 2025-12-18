# Phunkie PHPStan Extension

PHPStan extensions for the [Phunkie](https://github.com/phunkie/phunkie) functional programming library.

## Installation

```bash
composer require --dev phunkie/phpstan
```

The extension is automatically registered via PHPStan's extension mechanism.

## What it provides

### Generic Type Transformation

Phunkie uses container types (IO, Option, Validation, etc.) that transform their type parameters through method calls. PHPStan often loses this type information when closures are involved.

This extension provides a **single configurable resolver** that handles common patterns:

| Pattern | Description | Example |
|---------|-------------|---------|
| `wrap` | `F<A>` → `F<Wrapper<Fixed, A>>` | `IO<A>::attempt()` → `IO<Validation<Throwable, A>>` |
| `map` | `F<A>.f(A→B)` → `F<B>` | `IO<A>::map(fn)` → `IO<B>` |
| `pair` | `F<A>.f(A→B)` → `F<Pair<A, B>>` | `IO<A>::zipWith(fn)` → `IO<Pair<A, B>>` |
| `preserve` | `F<A>.method()` → `F<A>` | Methods that don't change type |

### Configuration

The extension is configured in `extension.neon`:

```yaml
services:
    -
        class: Phunkie\PHPStan\GenericMethodReturnTypeExtension
        arguments:
            targetClass: Phunkie\Effect\IO\IO
            methods:
                -
                    method: attempt
                    pattern: wrap
                    wrapper: Phunkie\Validation\Validation
                    fixedTypes: [Throwable]
                -
                    method: map
                    pattern: map
        tags:
            - phpstan.broker.dynamicMethodReturnTypeExtension
```

### Covariant Type Support

The Phunkie core library includes `@template-covariant` annotations so that:

- `Success<A>` is a `Validation<never, A>` (compatible with any `Validation<E, A>`)
- `Failure<E>` is a `Validation<E, never>` (compatible with any `Validation<E, A>`)

## Requirements

- PHP 8.2+
- PHPStan 2.0+
- Phunkie 1.0+

## License

MIT
