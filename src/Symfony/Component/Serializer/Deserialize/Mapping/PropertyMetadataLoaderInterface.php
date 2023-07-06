<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Mapping;

use Symfony\Component\Serializer\Deserialize\Configuration\Configuration;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string $className
     * @param array<string, mixed> $runtime
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(string $className, Configuration $configuration, array $runtime): array;
}
