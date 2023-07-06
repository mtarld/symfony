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

use Symfony\Component\Serializer\Serialize\Configuration;


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

    public Configuration $configuration;

    /**
     * @param list<TemplateVariation> $variations
     */
    public function __construct(array $variations)
    {
        usort($variations, fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b));

        $configuration = new Configuration();

        foreach ($variations as $variation) {
            $configuration = $variation->configure($configuration);
        }

        $this->variations = $variations;
        $this->configuration = $configuration;
    }
}
