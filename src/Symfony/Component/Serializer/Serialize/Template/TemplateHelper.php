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
final class TemplateHelper
{
    /**
     * @param array<string, mixed> $context
     */
    public function templateFilename(Type|string $type, string $format, array $context): string
    {
        $hash = hash('xxh128', (string) $type);

        if ([] !== $variant = $this->variant($context)) {
            usort($variant, fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b));

            $hash .= '.'.hash('xxh128', implode('_', array_map(fn (TemplateVariation $t): string => (string) $t, $variant)));
        }

        return sprintf('%s.%s.php', $hash, $format);
    }

    /**
     * @param class-string $className
     *
     * @return list<list<TemplateVariation>>
     */
    public function classTemplateVariants(string $className): array
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

        $variations = array_map(fn (string $g): TemplateVariation => TemplateVariation::createGroup($g), $groups);

        return $this->cartesianProduct($variations);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<TemplateVariation>
     */
    private function variant(array $context): array
    {
        $variant = [];
        foreach ($context['groups'] ?? [] as $group) {
            $variant[] = TemplateVariation::createGroup($group);
        }

        return $variant;
    }

    /**
     * @template T of mixed
     *
     * @param list<T> $variations
     *
     * @return list<list<T>>
     */
    private function cartesianProduct(array $variations): array
    {
        $variants = [[]];

        foreach ($variations as $variation) {
            foreach ($variants as $variant) {
                $variants[] = array_merge([$variation], $variant);
            }
        }

        return $variants;
    }
}
