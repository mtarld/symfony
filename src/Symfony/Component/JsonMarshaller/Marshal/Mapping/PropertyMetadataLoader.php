<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\Mapping;

use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;

/**
 * Loads basic properties marshalling metadata for a given $className.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function load(string $className, array $config, array $context): array
    {
        $result = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isPublic()) {
                continue;
            }

            $name = $marshalledName = $reflectionProperty->getName();
            $type = $this->typeExtractor->extractTypeFromProperty($reflectionProperty);

            $result[$marshalledName] = new PropertyMetadata($name, $type);
        }

        return $result;
    }
}
