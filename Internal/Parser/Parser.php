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
        private readonly ObjectParserInterface $objectParser,
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
                throw new \RuntimeException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']"', (string) $type));
            }

            /** @var Type $type */
            $type = Type::createFromString($context['union_selector'][(string) $type]);
        }

        return match (true) {
            $type->isNullable() => $this->nullableParser->parse($tokens, $type, $context, $this),
            $type->isScalar() => $this->scalarParser->parse($tokens, $type, $context),
            $type->isObject() => $this->parseObject($tokens, $type, $context),
            $type->isIterable() && $type->isList() => $this->listParser->parseIterable($tokens, $type->collectionValueType(), $context, $this),
            $type->isIterable() && $type->isDict() => $this->dictParser->parseIterable($tokens, $type->collectionValueType(), $context, $this),
            $type->isList() => $this->listParser->parse($tokens, $type->collectionValueType(), $context, $this),
            $type->isDict() => $this->dictParser->parse($tokens, $type->collectionValueType(), $context, $this),
            default => throw new \RuntimeException(sprintf('Unhandled "%s" type', $type)),
        };
    }

    /**
     * @param array<string, mixed> $context
     */
    private function parseObject(\Iterator $tokens, Type $type, array $context): object
    {
        $reflection = new \ReflectionClass($type->className());
        $object = $reflection->newInstanceWithoutConstructor();

        $setProperty = function (string $name, \Iterator $tokens) use ($reflection, &$object, $context): void {
            if (null !== ($hook = $context['hooks'][$reflection->getName()][$name] ?? null)) {
                $hook($reflection, $object, $context, fn (string $type, array $context): mixed => $this->parse($tokens, Type::createFromString($type), $context));

                return;
            }

            $object->{$name} = $this->parse($tokens, Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($name))), $context);
        };

        $this->objectParser->parse($tokens, $setProperty, $context);

        return $object;
    }
}
