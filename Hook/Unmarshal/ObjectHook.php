<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Hook\Unmarshal;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;
use Symfony\Component\Marshaller\Type\TypeHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ObjectHook
{
    /**
     * @var array<string, \ReflectionClass<object>>
     */
    private static array $classReflectionsCache = [];

    private readonly TypeHelper $typeHelper;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeHelper = new TypeHelper();
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{type: string, context: array<string, mixed>}
     */
    public function __invoke(string $type, array $context): array
    {
        return [
            'type' => $type,
            'context' => $this->addGenericParameterTypes($type, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addGenericParameterTypes(string $type, array $context): array
    {
        $generics = $this->typeHelper->extractGenerics($type);

        $genericType = $generics['genericType'];
        $genericParameters = $generics['genericParameters'];

        if (!class_exists($genericType)) {
            return $context;
        }

        $templates = $this->typeExtractor->extractTemplateFromClass(self::$classReflectionsCache[$type] = self::$classReflectionsCache[$type] ?? new \ReflectionClass($genericType));

        if (\count($templates) !== \count($genericParameters)) {
            throw new InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%s".', \count($genericParameters), $type, \count($templates), $genericType));
        }

        foreach ($genericParameters as $i => $genericParameter) {
            $context['_symfony']['unmarshal']['generic_parameter_types'][$genericType][$templates[$i]] = $genericParameter;
        }

        return $context;
    }
}
