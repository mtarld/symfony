<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Parser
{
    private readonly HookExtractor $hookExtractor;

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
            $result = $this->dictParser->parse($resource, $type, $context, $this);

            return ($type->isIterable() || null === $result) ? $result : iterator_to_array($result);
        }

        if ($type->isList()) {
            $result = $this->listParser->parse($resource, $type, $context, $this);

            return ($type->isIterable() || null === $result) ? $result : iterator_to_array($result);
        }

        if ($type->isObject()) {
            foreach ($this->listParser->parse($resource, $type, $context, $this) as $k => $v) {
                dd($e);
            }
        }

        throw new UnsupportedTypeException($type);
    }
}

