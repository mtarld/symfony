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
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Encode\EncodeAs;
use Symfony\Component\JsonEncoder\Encode\EncoderGenerator;
use Symfony\Component\JsonEncoder\Encode\Normalizer\DateTimeNormalizer;
use Symfony\Component\JsonEncoder\Encode\Normalizer\NormalizerInterface;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 *
 * @implements EncoderInterface<array{
 *   stream?: StreamWriterInterface,
 *   max_depth?: int,
 *   force_generation?: bool,
 * }>
 */
final class JsonEncoder implements EncoderInterface
{
    private EncoderGenerator $encoderGenerator;

    public function __construct(
        private ContainerInterface $normalizers,
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $encodersDir,
    ) {
        $this->encoderGenerator = new EncoderGenerator(new DataModelBuilder($propertyMetadataLoader), $encodersDir);
    }

    public function encode(mixed $data, Type $type, array $config = []): \Traversable&\Stringable
    {
        $stream = $config['stream'] ?? null;
        if (null !== $stream && method_exists($stream, 'getResource')) {
            $stream = $stream->getResource();
        }

        $path = $this->encoderGenerator->generate($type, match (true) {
            $stream instanceof StreamWriterInterface => EncodeAs::STREAM,
            null !== $stream => EncodeAs::RESOURCE,
            default => EncodeAs::STRING,
        }, $config);

        if (null !== $stream) {
            (require $path)($data, $stream, $this->normalizers, $config);

            return new Encoded(new \EmptyIterator());
        }

        return new Encoded((require $path)($data, $this->normalizers, $config));
    }

    /**
     * @param array<string, NormalizerInterface> $normalizers
     */
    public static function create(array $normalizers = [], ?string $encodersDir = null): static
    {
        $encodersDir ??= sys_get_temp_dir().'/json_encoder/encoder';
        $normalizers += [
            'json_encoder.normalizer.date_time' => new DateTimeNormalizer(),
        ];

        $normalizersContainer = new class($normalizers) implements ContainerInterface {
            public function __construct(
                private array $normalizers,
            ) {
            }

            public function has(string $id): bool
            {
                return isset($this->normalizers[$id]);
            }

            public function get(string $id): NormalizerInterface
            {
                return $this->normalizers[$id];
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
                    $normalizersContainer,
                ),
            ),
            $typeContextFactory,
        );

        return new self($normalizersContainer, $propertyMetadataLoader, $encodersDir);
    }
}
