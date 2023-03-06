<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\ContextBuilder\MarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\ContextBuilder\UnmarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Hook\Marshal as MarshalHook;
use Symfony\Component\Marshaller\Hook\Unmarshal as UnmarshalHook;
use Symfony\Component\Marshaller\Stream\StreamInterface;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<MarshalContextBuilderInterface>    $marshalContextBuilders
     * @param iterable<GenerationContextBuilderInterface> $generationContextBuilders
     * @param iterable<UnmarshalContextBuilderInterface>  $unmarshalContextBuilders
     */
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
        private readonly iterable $marshalContextBuilders,
        private readonly iterable $generationContextBuilders,
        private readonly iterable $unmarshalContextBuilders,
        private readonly string $templateCacheDir,
    ) {
    }

    public function marshal(mixed $data, string $format, StreamInterface $output, Context $context = null): void
    {
        /** @var TypeOption|null $typeOption */
        $typeOption = $context?->get(TypeOption::class);
        $type = $typeOption?->type ?? get_debug_type($data);

        $rawContext = $this->buildMarshalContext($type, $context);

        // if template does not exist, it'll be generated therefore raw context must be filled accordingly
        if (!file_exists(sprintf('%s/%s.%s.php', $this->templateCacheDir, md5($type), $format))) {
            $rawContext = $this->buildGenerationContext($type, $context, $rawContext);
        }

        marshal($data, $output->resource(), $format, $rawContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildGenerationContext($type, $context));
    }

    public function unmarshal(StreamInterface $input, string $type, string $format, Context $context = null): mixed
    {
        return unmarshal($input->resource(), $type, $format, $this->buildUnmarshalContext($type, $context));
    }

    /**
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    private function buildMarshalContext(string $type, ?Context $context, array $rawContext = []): array
    {
        $context = $context ?? new Context();
        $rawContext += [
            'cache_dir' => $this->templateCacheDir,
            'type' => $type,
        ];

        foreach ($this->marshalContextBuilders as $builder) {
            $rawContext = $builder->build($type, $context, $rawContext);
        }

        return $rawContext;
    }

    /**
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    private function buildGenerationContext(string $type, ?Context $context, array $rawContext = []): array
    {
        $context = $context ?? new Context();
        $rawContext += [
            'hooks' => [
                'object' => (new MarshalHook\ObjectHook($this->typeExtractor))(...),
                'property' => (new MarshalHook\PropertyHook($this->typeExtractor))(...),
            ],
        ];

        foreach ($this->generationContextBuilders as $builder) {
            $rawContext = $builder->build($type, $context, $rawContext);
        }

        return $rawContext;
    }

    /**
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    private function buildUnmarshalContext(string $type, ?Context $context, array $rawContext = []): array
    {
        $context = $context ?? new Context();
        $rawContext += [
            'hooks' => [
                // TODO from context builder?
                'object' => (new UnmarshalHook\ObjectHook($this->typeExtractor))(...),
                'property' => (new UnmarshalHook\PropertyHook($this->typeExtractor))(...),
            ],
        ];

        foreach ($this->unmarshalContextBuilders as $builder) {
            $rawContext = $builder->build($type, $context, $rawContext);
        }

        return $rawContext;
    }
}
