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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface PropertyHookInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array{name?: string, type?: string, accessor?: string, context?: array<string, mixed>}
     */
    public function __invoke(\ReflectionProperty $property, string $accessor, array $context): array;
}
