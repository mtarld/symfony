<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;

final class SerializerOptions
{
    private ?string $deserializationPath = null;

    public function getDeserializationPath(): ?string
    {
        return $this->deserializationPath;
    }

    public function setDeserializationPath(?string $deserializationPath): self
    {
        $this->deserializationPath = $deserializationPath;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->deserializationPath ??= $other->deserializationPath;

        return $this;
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function toLegacyContext(): array
    {
        return [
            'deserialization_path' => $this->getDeserializationPath(),
        ];
    }
}
