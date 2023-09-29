<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\Mapping;

use Symfony\Component\JsonMarshaller\MarshallerInterface;

/**
 * Loads properties marshalling metadata for a given $className.
 *
 * This metadata can be used by the {@see Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilderInterface}
 * to create a more appropriate {@see Symfony\Component\JsonMarshaller\Marshal\DataModel\ObjectNode}.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-import-type MarshalConfig from MarshallerInterface
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string         $className
     * @param MarshalConfig        $config
     * @param array<string, mixed> $context
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(string $className, array $config, array $context): array;
}
