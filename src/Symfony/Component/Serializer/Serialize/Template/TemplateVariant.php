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
final readonly class TemplateVariant
{
    /**
     * @var list<TemplateVariation>
     */
    public array $variations;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    /**
     * @param list<TemplateVariation> $variations
     */
    public function __construct(array $variations)
    {
        usort($variations, fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b));

        $context = [];
        foreach ($variations as $variation) {
            $context = $variation->updateContext($context);
        }

        $this->variations = $variations;
        $this->context = $context;
    }
}
