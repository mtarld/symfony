<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Hook\Deserialize;

use Symfony\Component\SerDes\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface ObjectHookInterface
{
    /**
     * @param array<string, array{name: string, value_provider: callable(Type): mixed}> $properties
     * @param array<string, mixed>                                                      $context
     *
     * @return array{properties?: array<string, array{name: string, value_provider: callable(Type): mixed}>, context?: array<string, mixed>}
     */
    public function __invoke(Type $type, array $properties, array $context): array;
}
