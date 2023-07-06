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

use Symfony\Component\Serializer\Deserialize\Unmarshaller\UnmarshallerInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
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

    public function deserialize(mixed $input, Type|string $type, string $format, Configuration $configuration = null): mixed
    {
        if ($input instanceof StreamInterface) {
            $input = $input->resource();
        }

        if (\is_string($type)) {
            $type = Type::createFromString($type);
        }

        $configuration ??= new Configuration();
        $runtime = new Runtime(originalType: $type);

        /** @var UnmarshallerInterface|null $unmarshaller */
        $unmarshaller = $configuration->lazyUnmarshal ? ($this->lazyUnmarshallers[$format] ?? null) : ($this->eagerUnmarshallers[$format] ?? null);
        if (null === $unmarshaller) {
            throw new UnsupportedException(sprintf('"%s" format is not supported.', $format));
        }

        return $unmarshaller->unmarshal($input, $type, $configuration, $runtime);
    }
}
