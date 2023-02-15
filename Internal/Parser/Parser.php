<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
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
    private readonly HookExtractor $hookExtractor;
    private ReflectionTypeExtractor|null $reflectionTypeExtractor = null;

    public function __construct(
        private readonly ScalarParserInterface $scalarParser,
        private readonly ListParserInterface $listParser,
        private readonly DictParserInterface $dictParser,
    ) {
        $this->hookExtractor = new HookExtractor();
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public function parse(mixed $resource, Type|UnionType $type, array $context): mixed
    {
        if (null !== $hook = $this->hookExtractor->extractFromType($type, $context)) {
            $hookResult = $hook((string) $type, $context);

            $type = isset($hookResult['type']) ? Type::createFromString($hookResult['type']) : $type;
            $context = $hookResult['context'] ?? $context;
        }

        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][(string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = Type::createFromString($context['union_selector'][(string) $type]);
        }

        if ($type->isScalar()) {
            return $this->scalarParser->parse($resource, $type, $context);
        }

        if ($type->isDict()) {
            if (null === $boundaries = $this->dictParser->parse($resource, $type, $context)) {
                return null;
            }

            $result = $this->replaceDictValues($boundaries, $resource, $type->collectionValueType(), $context);

            return $type->isIterable() ? $result : iterator_to_array($result);
        }

        if ($type->isList()) {
            if (null === $boundaries = $this->listParser->parse($resource, $type, $context)) {
                return null;
            }

            $result = $this->replaceListValues($boundaries, $resource, $type->collectionValueType(), $context);

            return $type->isIterable() ? $result : iterator_to_array($result);
        }

        if ($type->isObject()) {
            if (null === $boundaries = $this->dictParser->parse($resource, $type, $context)) {
                return null;
            }

            $this->reflectionTypeExtractor = $this->reflectionTypeExtractor ?? new ReflectionTypeExtractor();

            $reflection = new \ReflectionClass($type->className());
            $object = $this->instantiateObject($reflection, $context);

            foreach ($boundaries as $key => $boundary) {
                try {
                    if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
                        $hook(
                            $reflection,
                            $object,
                            $key,
                            fn (string $type, array $context): mixed => $this->parse($resource, Type::createFromString($type), ['boundary' => $boundary] + $context),
                            $context,
                        );

                        continue;
                    }

                    // TODO test
                    if (!$reflection->hasProperty($key)) {
                        continue;
                    }

                    // TODO proxy (next step)
                    $object->{$key} = $this->parse(
                        $resource,
                        Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key))),
                        ['boundary' => $boundary] + $context,
                    );
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

        throw new UnsupportedTypeException($type);
    }

    /**
     * @param \Iterator<string, array{offset: int, length: int}> $boundaries
     * @param resource                                           $resource
     * @param array<string, mixed>                               $context
     *
     * @return \Iterator<mixed>
     */
    private function replaceListValues(\Iterator $boundaries, mixed $resource, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($boundaries as $boundary) {
            yield $this->parse($resource, $type, ['boundary' => $boundary] + $context);
        }
    }

    /**
     * @param \Iterator<string, array{offset: int, length: int}> $boundaries
     * @param resource                                           $resource
     * @param array<string, mixed>                               $context
     *
     * @return \Iterator<string, mixed>
     */
    private function replaceDictValues(\Iterator $boundaries, mixed $resource, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->parse($resource, $type, ['boundary' => $boundary] + $context);
        }
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

            $exception = new InvalidConstructorArgumentException($parameter->getName(), $class->getName());
            if (!($context['collect_errors'] ?? false)) {
                throw $exception;
            }

            $context['collected_errors'][] = $exception;
            $validContructor = false;
        }

        return $validContructor ? $class->newInstanceArgs($parameters) : $class->newInstanceWithoutConstructor();
    }
}
