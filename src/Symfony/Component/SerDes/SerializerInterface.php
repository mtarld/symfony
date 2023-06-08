<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes;

use Symfony\Component\SerDes\Context\ContextInterface;
use Symfony\Component\SerDes\Exception\PartialDeserializationException;
use Symfony\Component\SerDes\Stream\StreamInterface;
use Symfony\Component\SerDes\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface SerializerInterface
{
    /**
     * @param StreamInterface|resource              $output
     * @param ContextInterface|array<string, mixed> $context
     */
    public function serialize(mixed $data, string $format, mixed $output, ContextInterface|array $context = []): void;

    /**
     * @param StreamInterface|resource              $input
     * @param ContextInterface|array<string, mixed> $context
     *
     * @throws PartialDeserializationException
     */
    public function deserialize(mixed $input, Type|string $type, string $format, ContextInterface|array $context = []): mixed;
}
