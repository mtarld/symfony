<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @internal
 */
final class Parser
{
    private readonly ReflectionTypeExtractor $reflectionTypeExtractor;

    public function __construct(
        private readonly NullableParserInterface $nullableParser,
        private readonly ScalarParserInterface $scalarParser,
        private readonly ListParserInterface $listParser,
        private readonly DictParserInterface $dictParser,
    ) {
        $this->reflectionTypeExtractor = new ReflectionTypeExtractor();
    }

    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     */
    public function parse(\Iterator $tokens, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][(string) $type])) {
                throw new \RuntimeException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
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
            return $this->scalarParser->parse($tokens, $type, $context);
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

        throw new \RuntimeException(sprintf('Unhandled "%s" type', $type));
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
     */
    private function parseObject(\Iterator $tokens, Type $type, array $context): object
    {
        $reflection = new \ReflectionClass($type->className());
        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($this->dictParser->parse($tokens, $context) as $key) {
            if (null !== ($hook = $context['hooks'][$reflection->getName()][$key] ?? null)) {
                $hook($reflection, $object, $context, fn (string $type, array $context): mixed => $this->parse($tokens, Type::createFromString($type), $context));

                continue;
            }

            $object->{$key} = $this->parse($tokens, Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key))), $context);
        }

        return $object;
    }
}
