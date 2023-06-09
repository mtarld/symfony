<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer;

use Symfony\Component\Serializer\Stream\StreamInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface SerializeInterface
{
    /**
     * @param StreamInterface|resource              $output
     * @param ContextInterface|array<string, mixed> $context
     */
    public function __invoke(mixed $data, string $format, mixed $output, ContextInterface|array $context = []): void;
}
