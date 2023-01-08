<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Hook\UnmarshalHookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Parser
{
    private UnmarshalHookExtractor|null $hookExtractor = null;
    private ReflectionTypeExtractor|null $reflectionTypeExtractor = null;

    public function __construct(
        private readonly NullableParserInterface $nullableParser,
        private readonly ScalarParserInterface $scalarParser,
        private readonly ListParserInterface $listParser,
        private readonly DictParserInterface $dictParser,
    ) {
    }

    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     */
    public function parse(\Iterator $tokens, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][(string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = Type::createFromString($context['union_selector'][(string) $type]);
        }

        if ($type->isNullable()) {
            return $this->nullableParser->parse($tokens, function (\Iterator $tokens) use ($type, $context): mixed {
                return $this->parse($tokens, Type::createFromString(substr((string) $type, 1)), $context);
            }, $context);
        }

        if ($type->isScalar()) {
            $result = $this->scalarParser->parse($tokens, $type, $context);

            return match ($type->name()) {
                'int' => (int) $result,
                'float' => (float) $result,
                'string' => (string) $result,
                'bool' => (bool) $result,
                default => throw new LogicException(sprintf('Cannot cast value to "%s".', $type->name())),
            };
        }

        if ($type->isList()) {
            $result = $this->parseList($tokens, $type, $context);

            return $type->isIterable() ? $result : iterator_to_array($result);
        }

        if ($type->isDict()) {
            $result = $this->parseDict($tokens, $type, $context);

            return $type->isIterable() ? $result : iterator_to_array($result);
        }

        if ($type->isObject()) {
            return $this->parseObject($tokens, $type, $context);
        }

        throw new UnsupportedTypeException($type);
    }

    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>
     */
    private function parseList(\Iterator $tokens, Type $type, array $context): \Iterator
    {
        $valueType = $type->collectionValueType();

        foreach ($this->listParser->parse($tokens, $context) as $_) {
            yield $this->parse($tokens, $valueType, $context);
        }
    }

    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @return \Iterator<string, mixed>
     */
    private function parseDict(\Iterator $tokens, Type $type, array $context): \Iterator
    {
        $valueType = $type->collectionValueType();

        foreach ($this->dictParser->parse($tokens, $context) as $key) {
            yield $key => $this->parse($tokens, $valueType, $context);
        }
    }

    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @throws UnexpectedTypeException
     */
    private function parseObject(\Iterator $tokens, Type $type, array $context): object
    {
        $this->hookExtractor = $this->hookExtractor ?? new UnmarshalHookExtractor();
        $this->reflectionTypeExtractor = $this->reflectionTypeExtractor ?? new ReflectionTypeExtractor();

        $reflection = new \ReflectionClass($type->className());
        $object = $this->instantiateObject($reflection, $context);

        foreach ($this->dictParser->parse($tokens, $context) as $key) {
            try {
                if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
                    $hook(
                        $reflection,
                        $object,
                        $key,
                        fn (string $type, array $context): mixed => $this->parse($tokens, Type::createFromString($type), $context),
                        $context,
                    );

                    continue;
                }

                $object->{$key} = $this->parse($tokens, Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key))), $context);
            } catch (\TypeError $e) {
                $exception = new UnexpectedTypeException($e->getMessage());
                if (!($context['collect_errors'] ?? false)) {
                    throw $exception;
                }

                $context['collected_errors'][] = $exception;
            }
        }

        return $object;
    }

    /**
     * @template T of object
     *
     * @param \ReflectionClass<T>  $class
     * @param array<string, mixed> $context
     *
     * @return T
     *
     * @throws InvalidConstructorArgumentException
     */
    private function instantiateObject(\ReflectionClass $class, array $context): object
    {
        if (null === $constructor = $class->getConstructor()) {
            return new ($class->getName())();
        }

        if (!$constructor->isPublic()) {
            return $class->newInstanceWithoutConstructor();
        }

        $parameters = [];
        $validContructor = true;

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $parameters[] = $parameter->getDefaultValue();

                continue;
            }

            if ($parameter->getType()?->allowsNull()) {
                $parameters[] = null;

                continue;
            }

            $exception = InvalidConstructorArgumentException::createForReflectors($parameter, $class);
            if (!($context['collect_errors'] ?? false)) {
                throw $exception;
            }

            $context['collected_errors'][] = $exception;
            $validContructor = false;
        }

        return $validContructor ? $class->newInstanceArgs($parameters) : $class->newInstanceWithoutConstructor();
    }
}
