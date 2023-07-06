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
interface TemplateVariationExtractorInterface
{
    /**
     * @param class-string $className
     *
     * @return list<TemplateVariation>
     */
    public function extractFromClass(string $className): array;

    /**
     * @return list<TemplateVariation>
     */
    public function extractFromConfiguration(Configuration $context): array;
}
