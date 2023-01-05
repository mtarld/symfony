<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Stream\StreamInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface MarshallerInterface
{
    public function marshal(mixed $data, string $format, StreamInterface $output, Context $context = null): void;

    public function generate(string $type, string $format, Context $context = null): string;

    public function unmarshal(StreamInterface $input, string $type, string $format, Context $context = null): mixed;
}
