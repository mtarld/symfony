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

use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\Encode\EncoderGenerator;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\NumberTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\ValueTransformer\DateTimeToStringValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\NumberToStringValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @implements EncoderInterface<array<string, mixed>>
 *
 * @experimental
 */
final class JsonEncoder implements EncoderInterface
{
    private EncoderGenerator $encoderGenerator;

    public function __construct(
        private ContainerInterface $valueTransformers,
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $encodersDir,
    ) {
        $this->encoderGenerator = new EncoderGenerator($propertyMetadataLoader, $encodersDir);
    }

    public function encode(mixed $data, Type $type, array $options = []): \Traversable&\Stringable
    {
        $path = $this->encoderGenerator->generate($type, $options);

        return new Encoded((require $path)($data, $this->valueTransformers, $options));
    }

    /**
     * @param array<string, ValueTransformerInterface> $valueTransformers
     */
    public static function create(array $valueTransformers = [], ?string $encodersDir = null): self
    {
        $encodersDir ??= sys_get_temp_dir().'/json_encoder/encoder';
        $valueTransformers += [
            'json_encoder.value_transformer.date_time_to_string' => new DateTimeToStringValueTransformer(),
            'json_encoder.value_transformer.number_to_string' => new NumberToStringValueTransformer(),
        ];

        $valueTransformersContainer = new class($valueTransformers) implements ContainerInterface {
            public function __construct(
                private array $valueTransformers,
            ) {
            }

            public function has(string $id): bool
            {
                return isset($this->valueTransformers[$id]);
            }

            public function get(string $id): ValueTransformerInterface
            {
                return $this->valueTransformers[$id];
            }
        };

        $typeContextFactory = new TypeContextFactory(class_exists(PhpDocParser::class) ? new StringTypeResolver() : null);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new NumberTypePropertyMetadataLoader(
                new DateTimeTypePropertyMetadataLoader(
                    new AttributePropertyMetadataLoader(
                        new PropertyMetadataLoader(TypeResolver::create()),
                        $valueTransformersContainer,
                        TypeResolver::create(),
                    ),
                ),
            ),
            $typeContextFactory,
        );

        return new self($valueTransformersContainer, $propertyMetadataLoader, $encodersDir);
    }
}
