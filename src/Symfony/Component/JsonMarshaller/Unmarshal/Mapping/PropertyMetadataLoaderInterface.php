<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\Mapping;

/**
 * Loads properties unmarshalling metadata for a given $className.
 *
 * This metadata can be used by the {@see Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilderInterface}
 * to create a more appropriate {@see Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ObjectNode}.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     *
     * @return array<string, PropertyMetadata>
     */
    // TODO
    public function load(string $className, array $config, array $context): array;
}
