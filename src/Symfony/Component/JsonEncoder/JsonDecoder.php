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

use BcMath\Number;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\Decode\DecoderGenerator;
use Symfony\Component\JsonEncoder\Decode\Instantiator;
use Symfony\Component\JsonEncoder\Decode\LazyInstantiator;
use Symfony\Component\JsonEncoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\NumberTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\ValueTransformer\ScalarToNumberValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\StringToDateTimeValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @implements DecoderInterface<array<string, mixed>>
 *
 * @experimental
 */
final class JsonDecoder implements DecoderInterface
{
    private DecoderGenerator $decoderGenerator;
    private Instantiator $instantiator;
    private LazyInstantiator $lazyInstantiator;

    public function __construct(
        private ContainerInterface $valueTransformers,
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $decodersDir,
        string $lazyGhostsDir,
    ) {
        $this->decoderGenerator = new DecoderGenerator($propertyMetadataLoader, $decodersDir);
        $this->instantiator = new Instantiator();
        $this->lazyInstantiator = new LazyInstantiator($lazyGhostsDir);
    }

    public function decode($input, Type $type, array $options = []): mixed
    {
        $isStream = \is_resource($input);
        $path = $this->decoderGenerator->generate($type, $isStream, $options);

        return (require $path)($input, $this->valueTransformers, $isStream ? $this->lazyInstantiator : $this->instantiator, $options);
    }

    /**
     * @param array<string, ValueTransformerInterface> $valueTransformers
     */
    public static function create(array $valueTransformers = [], ?string $decodersDir = null, ?string $lazyGhostsDir = null): self
    {
        $decodersDir ??= sys_get_temp_dir().'/json_encoder/decoder';
        $lazyGhostsDir ??= sys_get_temp_dir().'/json_encoder/lazy_ghost';
        $valueTransformers += [
            'json_encoder.value_transformer.string_to_date_time' => new StringToDateTimeValueTransformer(),
            'json_encoder.value_transformer.scalar_to_bc_math_number' => new ScalarToNumberValueTransformer(Number::class),
            'json_encoder.value_transformer.scalar_to_gmp_number' => new ScalarToNumberValueTransformer(\GMP::class),
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

        return new self($valueTransformersContainer, $propertyMetadataLoader, $decodersDir, $lazyGhostsDir);
    }
}
