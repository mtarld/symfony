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

use Symfony\Component\Serializer\Exception\PartialDeserializationException;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface DeserializeInterface
{
    /**
     * @param StreamInterface|resource              $input
     * @param ContextInterface|array<string, mixed> $context
     *
     * @throws PartialDeserializationException
     */
    public function __invoke(mixed $input, Type|string $type, string $format, ContextInterface|array $context = []): mixed;
}
