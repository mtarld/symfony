<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\ContextInterface;
use Symfony\Component\Marshaller\Exception\PartialUnmarshalException;
use Symfony\Component\Marshaller\Stream\StreamInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface MarshallerInterface
{
    /**
     * @param ContextInterface|array<string, mixed> $context
     */
    public function marshal(mixed $data, string $format, StreamInterface $output, ContextInterface|array $context = []): void;

    /**
     * @param ContextInterface|array<string, mixed> $context
     *
     * @throws PartialUnmarshalException
     */
    public function unmarshal(StreamInterface $input, string $type, string $format, ContextInterface|array $context = []): mixed;
}
