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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.3
 */
interface ObjectHookInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array{type?: string, context?: array<string, mixed>}
     */
    public function __invoke(string $type, array $context): array;
}
