<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer;

use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonStreamer\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonStreamer\Mapping\Write\AttributePropertyMetadataLoader;
use Symfony\Component\JsonStreamer\ValueTransformer\DateTimeToStringValueObjectTransformer;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueObjectTransformerInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\JsonStreamer\Write\StreamWriterGenerator;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @implements StreamWriterInterface<array{
 *     include_null_properties?: bool,
 *     ...<string, mixed>,
 * }>
 */
final class JsonStreamWriter implements StreamWriterInterface
{
    private StreamWriterGenerator $streamWriterGenerator;

    /**
     * @param ContainerInterface|array<string, ValueTransformerInterface> $valueTransformers
     */
    public function __construct(
        private ContainerInterface|array $valueTransformers,
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $streamWritersDir,
    ) {
        if ($valueTransformers instanceof ContainerInterface) {
            // deprecate if ContainerInterface
        } else {
            foreach ($valueTransformers as $k => $v) {
                if ($v instanceof ValueObjectTransformerInterface && !class_exists($k)) {
                    // throw need class-string
                }
            }
        }

        $this->streamWriterGenerator = new StreamWriterGenerator($propertyMetadataLoader, $this->valueTransformers, $streamWritersDir);
    }

    public function write(mixed $data, Type $type, array $options = []): \Traversable&\Stringable
    {
        $path = $this->streamWriterGenerator->generate($type, $options);
        $chunks = (require $path)($data, $this->valueTransformers, $options);

        return new
        /**
         * @implements \IteratorAggregate<int, string>
         */
        class($chunks) implements \IteratorAggregate, \Stringable {
            /**
             * @param \Traversable<string> $chunks
             */
            public function __construct(
                private \Traversable $chunks,
            ) {
            }

            public function getIterator(): \Traversable
            {
                return $this->chunks;
            }

            public function __toString(): string
            {
                $string = '';
                foreach ($this->chunks as $chunk) {
                    $string .= $chunk;
                }

                return $string;
            }
        };
    }

    /**
     * @param array<string, ValueTransformerInterface> $valueTransformers
     */
    public static function create(array $valueTransformers = [], ?string $streamWritersDir = null): self
    {
        $streamWritersDir ??= sys_get_temp_dir().'/json_streamer/write';
        $valueTransformers += [
            \DateTimeInterface::class => new DateTimeToStringValueObjectTransformer(),
        ];

        $typeContextFactory = new TypeContextFactory(class_exists(PhpDocParser::class) ? new StringTypeResolver() : null);

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader(TypeResolver::create()),
                $valueTransformers,
                TypeResolver::create(),
            ),
            $typeContextFactory,
        );

        return new self($valueTransformers, $propertyMetadataLoader, $streamWritersDir);
    }
}
