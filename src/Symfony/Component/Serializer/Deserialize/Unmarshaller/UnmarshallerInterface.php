<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Unmarshaller;

use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface UnmarshallerInterface
{
    /**
     * @param resource $resource
     * @param callable(class-string, array<string, callable(): mixed>, array<string, mixed>): object $instantiator
     * @param array<string, mixed> $context
     */
    public function unmarshal(mixed $resource, Type $type, callable $instantiator, array $context): mixed;
}
