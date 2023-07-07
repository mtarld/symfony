<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize;

use Symfony\Component\Serializer\Deserialize\Configuration\Configuration;
use Symfony\Component\Serializer\Deserialize\Unmarshaller\UnmarshallerInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Deserializer implements DeserializerInterface
{
    /**
     * @param array<string, UnmarshallerInterface> $eagerUnmarshallers
     * @param array<string, UnmarshallerInterface> $lazyUnmarshallers
     */
    public function __construct(
        private readonly array $eagerUnmarshallers,
        private readonly array $lazyUnmarshallers,
    ) {
    }

    public function deserialize(StreamInterface|string $input, Type|string $type, string $format, Configuration $configuration = null): mixed
    {
        if (\is_string($input)) {
            $input = new MemoryStream($input);
        }

        if (\is_string($type)) {
            $type = Type::createFromString($type);
        }

        $configuration ??= new Configuration();

        /** @var UnmarshallerInterface|null $unmarshaller */
        $unmarshaller = $configuration->lazyUnmarshal() ? ($this->lazyUnmarshallers[$format] ?? null) : ($this->eagerUnmarshallers[$format] ?? null);
        if (null === $unmarshaller) {
            throw new UnsupportedException(sprintf('"%s" format is not supported.', $format));
        }

        $context = [
            'original_type' => $type,
            'offset' => 0,
            'length' => -1,
        ];

        return $unmarshaller->unmarshal($input->resource(), $type, $configuration, $context);
    }
}
