<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Hook;

use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface ObjectHookInterface
{
    /**
     * @param array<string, array{name: string, type: Type, accessor: string}> $properties
     * @param array<string, mixed>                                             $context
     *
     * @return array{properties?: array<string, array{name: string, type: Type, accessor: string}>, context?: array<string, mixed>}
     */
    public function __invoke(Type $type, string $accessor, array $properties, array $context): array;
}
