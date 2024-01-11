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
use Symfony\Component\JsonEncoder\Decode\DecodeFrom;
use Symfony\Component\JsonEncoder\Decode\DecoderGenerator;
use Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface;
use Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;
use Symfony\Component\JsonEncoder\Stream\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final readonly class JsonDecoder implements DecoderInterface
{
    public function __construct(
        private DecoderGenerator $decoderGenerator,
        private InstantiatorInterface $instantiator,
        private LazyInstantiatorInterface $lazyInstantiator,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array{
     *   date_time_format?: string,
     *   force_generation?: bool,
     * } $config
     */
    public function decode(StreamReaderInterface|\Traversable|\Stringable|string $input, Type $type, array $config = []): mixed
    {
        if ($input instanceof \Traversable && !$input instanceof StreamReaderInterface) {
            $chunks = $input;
            $input = new BufferedStream();
            foreach ($chunks as $chunk) {
                $input->write($chunk);
            }
        }

        $isStream = $input instanceof StreamReaderInterface;
        $isResourceStream = $isStream && method_exists($input, 'getResource');

        $path = $this->decoderGenerator->generate($type, match (true) {
            $isResourceStream => DecodeFrom::RESOURCE,
            $isStream => DecodeFrom::STREAM,
            default => DecodeFrom::STRING,
        }, $config);

        return (require $path)($isResourceStream ? $input->getResource() : $input, $config, $isStream ? $this->lazyInstantiator : $this->instantiator, $this->runtimeServices);
    }
}
