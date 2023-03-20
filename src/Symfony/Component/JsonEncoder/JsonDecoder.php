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
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Decode\DecodeFrom;
use Symfony\Component\JsonEncoder\Decode\DecoderGenerator;
use Symfony\Component\JsonEncoder\Decode\Instantiator;
use Symfony\Component\JsonEncoder\Decode\LazyInstantiator;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;
use Symfony\Component\JsonEncoder\Stream\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 *
 * @implements DecoderInterface<array{
 *   date_time_format?: string,
 *   force_generation?: bool,
 * }>
 */
final readonly class JsonDecoder implements DecoderInterface
{
    private DecoderGenerator $decoderGenerator;
    private Instantiator $instantiator;
    private LazyInstantiator $lazyInstantiator;

    public function __construct(
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $cacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
        $this->decoderGenerator = new DecoderGenerator(new DataModelBuilder($propertyMetadataLoader, $runtimeServices), $cacheDir);
        $this->instantiator = new Instantiator();
        $this->lazyInstantiator = new LazyInstantiator($cacheDir);
    }

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
