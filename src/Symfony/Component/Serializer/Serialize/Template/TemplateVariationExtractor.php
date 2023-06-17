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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class TemplateVariationExtractor implements TemplateVariationExtractorInterface
{
    public function extractFromClass(string $className): array
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

        $groups = array_values(array_unique($groups));

        return array_map(fn (string $g): TemplateVariation => new GroupTemplateVariation($g), $groups);
    }

    public function extractFromContext(array $context): array
    {
        $variations = [];

        foreach ($context['groups'] ?? [] as $group) {
            $variations[] = new GroupTemplateVariation($group);
        }

        return $variations;
    }
}
