<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\Encode\EncodeAs;
use Symfony\Component\JsonEncoder\Encode\EncoderGenerator;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final readonly class JsonEncoder implements EncoderInterface
{
    public function __construct(
        private EncoderGenerator $encoderGenerator,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array{
     *   type?: Type,
     *   stream?: StreamWriterInterface,
     *   max_depth?: int,
     *   date_time_format?: string,
     *   force_generation?: bool,
     *   json_encode_flags?: int,
     * } $config
     */
    public function encode(mixed $data, array $config = []): \Traversable&\Stringable
    {
        if (null === ($type = $config['type'] ?? null)) {
            $type = \is_object($data) ? Type::object($data::class) : Type::builtin(get_debug_type($data));
        }

        $stream = $config['stream'] ?? null;
        $isResourceStream = null !== $stream && method_exists($stream, 'getResource');

        $path = $this->encoderGenerator->generate($type, match (true) {
            $isResourceStream => EncodeAs::RESOURCE,
            null !== $stream => EncodeAs::STREAM,
            default => EncodeAs::STRING,
        }, $config);

        if (null !== $stream) {
            (require $path)($data, $isResourceStream ? $stream->getResource() : $stream, $config, $this->runtimeServices);

            return new Encoded(new \EmptyIterator());
        }

        return new Encoded((require $path)($data, $config, $this->runtimeServices));
    }
}
