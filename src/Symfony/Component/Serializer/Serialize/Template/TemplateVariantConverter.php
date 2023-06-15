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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class TemplateVariantConverter
{
    /**
     * @param array<string, mixed> $context
     */
    public function fromContext(array $context): TemplateVariant
    {
        $variations = [];

        foreach ($context['groups'] ?? [] as $group) {
            $variations[] = new TemplateVariation('group', $group);
        }

        return new TemplateVariant($variations);
    }

    /**
     * @return array<string, mixed>
     */
    public function toContext(TemplateVariant $variant): array
    {
        $context = [
            'groups' => [],
        ];

        foreach ($variant->variations as $variation) {
            if ('group' === $variation->type) {
                $context['groups'][] = $variation->value;
            }
        }
    }
}
