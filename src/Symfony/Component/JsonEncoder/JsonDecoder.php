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
use Symfony\Component\JsonEncoder\Decode\Denormalizer\DateTimeDenormalizer;
use Symfony\Component\JsonEncoder\Decode\Denormalizer\DenormalizerInterface;
use Symfony\Component\JsonEncoder\Decode\Instantiator;
use Symfony\Component\JsonEncoder\Decode\LazyInstantiator;
use Symfony\Component\JsonEncoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;
use Symfony\Component\JsonEncoder\Stream\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 *
 * @implements DecoderInterface<array{
 *   force_generation?: bool,
 * }>
 */
final class JsonDecoder implements DecoderInterface
{
    private DecoderGenerator $decoderGenerator;
    private Instantiator $instantiator;
    private LazyInstantiator $lazyInstantiator;

    public function __construct(
        private ContainerInterface $denormalizers,
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $decodersDir,
        string $lazyGhostsDir,
    ) {
        $this->decoderGenerator = new DecoderGenerator(new DataModelBuilder($propertyMetadataLoader), $decodersDir);
        $this->instantiator = new Instantiator();
        $this->lazyInstantiator = new LazyInstantiator($lazyGhostsDir);
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

        return (require $path)($isResourceStream ? $input->getResource() : $input, $this->denormalizers, $isStream ? $this->lazyInstantiator : $this->instantiator, $config);
    }

    /**
     * @param array<string, DenormalizerInterface> $denormalizers
     */
    public static function create(array $denormalizers = [], ?string $decodersDir = null, ?string $lazyGhostsDir = null): static
    {
        $decodersDir ??= sys_get_temp_dir().'/json_encoder/decoder';
        $lazyGhostsDir ??= sys_get_temp_dir().'/json_encoder/lazy_ghost';
        $denormalizers += [
            'json_encoder.denormalizer.date_time' => new DateTimeDenormalizer(),
        ];

        $denormalizersContainer = new class($denormalizers) implements ContainerInterface {
            public function __construct(
                private array $denormalizers,
            ) {
            }

            public function has(string $id): bool
            {
                return isset($this->denormalizers[$id]);
            }

            public function get(string $id): DenormalizerInterface
            {
                return $this->denormalizers[$id];
            }
        };

        try {
            $stringTypeResolver = new StringTypeResolver();
        } catch (\Throwable) {
        }

        $typeContextFactory = new TypeContextFactory($stringTypeResolver ?? null);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(
                new AttributePropertyMetadataLoader(
                    new PropertyMetadataLoader(TypeResolver::create()),
                    $denormalizersContainer,
                ),
            ),
            $typeContextFactory,
        );

        return new self($denormalizersContainer, $propertyMetadataLoader, $decodersDir, $lazyGhostsDir);
    }
}
