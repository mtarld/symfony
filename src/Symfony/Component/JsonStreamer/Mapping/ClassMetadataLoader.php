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

/**
 * Loads basic class elements stream reading/writing metadata for a given $className.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ClassMetadataLoader implements ClassMetadataLoaderInterface
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
            $staticValue = null;

            if ($reflectionProperty->isStatic()) {
                $staticValue = $reflectionProperty->getValue();
            }

            $result[$streamedName] = new PropertyMetadata($name, $type, [], [], $staticValue);
        }

        foreach ($classReflection->getReflectionConstants() as $reflectionConstant) {
            if (!$reflectionConstant->isPublic()) {
                continue;
            }

            $streamedName = $reflectionConstant->getName();

            $result[$streamedName] = new ConstantMetadata($reflectionConstant->getValue());
        }

        return $result;
    }
}
