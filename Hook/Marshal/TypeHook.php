<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Hook\Marshal;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TypeHook
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{type: string, accessor: string, context: array<string, mixed>}
     */
    public function __invoke(string $type, string $accessor, array $context): array
    {
        $typeFormatter = isset($context['_symfony']['marshal']['type_formatter'][$type]) ? new \ReflectionFunction($context['_symfony']['marshal']['type_formatter'][$type]) : null;

        return [
            'type' => null !== $typeFormatter ? $this->typeExtractor->extractFromFunctionReturn($typeFormatter) : $type,
            'accessor' => $this->accessor($type, $typeFormatter, $accessor, $context),
            'context' => $context,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $type, ?\ReflectionFunction $typeFormatter, string $accessor, array $context): string
    {
        if (null === $typeFormatter) {
            return $accessor;
        }

        if (!$typeFormatter->getClosureScopeClass()?->hasMethod($typeFormatter->getName()) || !$typeFormatter->isStatic()) {
            throw new InvalidArgumentException(sprintf('Type formatter "%s" must be a static method.', $type));
        }

        if (($returnType = $typeFormatter->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new InvalidArgumentException(sprintf('Return type of type formatter "%s" must not be "void" nor "never".', $type));
        }

        if (null !== ($contextParameter = $typeFormatter->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new InvalidArgumentException(sprintf('Second argument of type formatter "%s" must be an array.', $type));
            }
        }

        return sprintf('%s::%s(%s, $context)', $typeFormatter->getClosureScopeClass()->getName(), $typeFormatter->getName(), $accessor);
    }
}
