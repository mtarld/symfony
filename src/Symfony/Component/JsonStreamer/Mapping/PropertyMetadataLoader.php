<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Mapping;

use Symfony\Component\JsonStreamer\Exception\RuntimeException;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Loads basic properties stream reading/writing metadata for a given $className.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private TypeResolverInterface $typeResolver,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $result = [];

        try {
            $classReflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($classReflection->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isPublic()) {
                continue;
            }

            $name = $streamedName = $reflectionProperty->getName();
            $type = $this->typeResolver->resolve($reflectionProperty);

            $metadata = [];
            foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $t) {
                $metadata[] = ['native' => $t, 'stream' => $t, 'transformers' => []];
            }

            $result[$streamedName] = new PropertyMetadata($name, $metadata, $metadata);
        }

        return $result;
    }
}
