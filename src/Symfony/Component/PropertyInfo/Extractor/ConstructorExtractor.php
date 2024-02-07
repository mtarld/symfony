<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\TypeInfo\BackwardCompatibilityHelper;
use Symfony\Component\TypeInfo\Type;

/**
 * Extracts the constructor argument type using ConstructorArgumentTypeExtractorInterface implementations.
 *
 * @author Dmitrii Poddubnyi <dpoddubny@gmail.com>
 */
final class ConstructorExtractor implements PropertyTypeExtractorInterface
{
    /**
     * @param iterable<int, ConstructorArgumentTypeExtractorInterface> $extractors
     */
    public function __construct(
        private readonly iterable $extractors = [],
    ) {
    }

    public function getType(string $class, string $property, array $context = []): ?Type
    {
        foreach ($this->extractors as $extractor) {
            $value = $extractor->getTypeFromConstructor($class, $property);
            if (null !== $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @deprecated since Symfony 7.1, use "getType" instead.
     */
    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s()" method is deprecated, use "%s::getType()" instead.', __METHOD__, self::class);

        return BackwardCompatibilityHelper::convertTypeToLegacyTypes($this->getType($class, $property, $context));
    }
}
