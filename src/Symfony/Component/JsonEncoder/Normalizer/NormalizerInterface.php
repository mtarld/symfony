<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Normalizer;

use Symfony\Component\TypeInfo\Type;

/**
 * Normalizes data during the encoding process.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
interface NormalizerInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function normalize(mixed $denormalized, array $config): mixed;

    public static function getNormalizedType(): Type;
}
