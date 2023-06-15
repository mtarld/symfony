<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class TemplateVariations
{
    /**
     * @return list<TemplateVariation>
     */
    public function typeVariations(Type $type): array
    {
        if ($type->isObject() && $type->hasClass()) {
            return $this->classVariations($type->className());
        }

        $variations = [];

        foreach ($type->genericParameterTypes() as $genericParameterType) {
            $variations = array_udiff(
                $variations,
                $this->typeVariations($genericParameterType),
                fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b),
            );
        }

        foreach ($type->unionTypes() as $unionType) {
            $variations = array_udiff(
                $variations,
                $this->typeVariations($unionType),
                fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b),
            );
        }

        usort($variations, fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b));

        return $variations;
    }

    /**
     * @param class-string $className
     *
     * @return list<TemplateVariation>
     */
    public function classVariations(string $className): array
    {
        $groups = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Groups::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Groups $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                array_push($groups, ...$attributeInstance->groups);
            }
        }

        return array_map(
            fn (string $g): TemplateVariation => new TemplateVariation('group', $g),
            array_values(array_unique($groups)),
        );
    }
}
