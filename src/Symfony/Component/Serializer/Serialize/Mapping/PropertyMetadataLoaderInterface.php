<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Metadata;

use Symfony\Component\Serializer\Deserialize\Configuration;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string         $className
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(Type $originalType, string $className, Configuration $configuration): array;
}
