<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Hook\Serialize;

use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface ObjectHookInterface
{
    /**
     * @param array<string, array{name: string, type: Type|UnionType, accessor: string}> $properties
     * @param array<string, mixed>                                                       $context
     *
     * @return array{properties?: array<string, array{name: string, type: Type|UnionType, accessor: string}>, context?: array<string, mixed>}
     */
    public function __invoke(Type $type, string $accessor, array $properties, array $context): array;
}
