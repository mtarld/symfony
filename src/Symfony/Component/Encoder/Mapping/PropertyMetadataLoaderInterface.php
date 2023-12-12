<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Mapping;

use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\EncoderInterface;

/**
 * Loads properties encoding/decoding metadata for a given $className.
 *
 * This metadata can be used by the DataModelBuilder to create
 * a more appropriate ObjectNode.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 *
 * @phpstan-import-type EncodeConfig from EncoderInterface
 * @phpstan-import-type DecodeConfig from DecoderInterface
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string              $className
     * @param EncodeConfig|DecodeConfig $config
     * @param array<string, mixed>      $context
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(string $className, array $config, array $context): array;
}
