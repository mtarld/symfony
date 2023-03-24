<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface ContextBuilderInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function buildSerializeContext(array $context, bool $willGenerateTemplate): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function buildDeserializeContext(array $context): array;
}
