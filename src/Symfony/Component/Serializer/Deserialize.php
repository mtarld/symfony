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

use Symfony\Component\Serializer\Context\ContextBuilder;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeFactory;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class Deserialize implements DeserializeInterface
{
    public function __construct(
        private ContextBuilder $contextBuilder,
    ) {
    }

    public function __invoke(mixed $input, Type|string $type, string $format, ContextInterface|array $context = []): mixed
    {
        if ($input instanceof StreamInterface) {
            $input = $input->resource();
        }

        if (\is_string($type)) {
            $type = TypeFactory::createFromString($type);
        }

        if ($context instanceof ContextInterface) {
            $context = $context->toArray();
        }

        $context = $this->contextBuilder->build($context, isSerialization: false);

        return deserialize($input, $type, $format, $context);
    }
}
