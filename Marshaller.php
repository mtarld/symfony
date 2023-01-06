<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\MarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\UnmarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\Marshal\ObjectHook as MarshalObjectHook;
use Symfony\Component\Marshaller\Hook\Marshal\PropertyHook as MarshalPropertyHook;
use Symfony\Component\Marshaller\Hook\Marshal\TypeHook as MarshalTypeHook;
use Symfony\Component\Marshaller\Hook\Unmarshal\PropertyHook as UnmarshalPropertyHook;
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
        private readonly string $cacheDir,
    ) {
    }

    public function marshal(mixed $data, string $format, StreamInterface $output, Context $context = null): void
    {
        $rawContext = $this->buildMarshalContext($context);
        $type = $rawContext['type'] ?? get_debug_type($data);

        // if template does not exist, it'll be generated therefore raw context must be filled accordingly
        if (!file_exists(sprintf('%s/%s.%s.php', $this->cacheDir, md5($type), $format))) {
            $rawContext = $this->buildGenerationContext($type, $context, $rawContext);
        }

        marshal($data, $output->stream(), $format, $rawContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildGenerationContext($type, $context));
    }

    public function unmarshal(StreamInterface $input, string $type, string $format, Context $context = null): mixed
    {
        return unmarshal($input->stream(), $type, $format, $this->buildUnmarshalContext($type, $context));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMarshalContext(?Context $context): array
    {
        $context = $context ?? new Context();
        $rawContext = ['cache_dir' => $this->cacheDir];

        foreach ($this->marshalContextBuilders as $builder) {
            $rawContext = $builder->build($context, $rawContext);
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
                'object' => (new MarshalObjectHook($this->typeExtractor))(...),
                'property' => (new MarshalPropertyHook($this->typeExtractor))(...),
                'type' => (new MarshalTypeHook($this->typeExtractor))(...),
            ],
        ];

        foreach ($this->generationContextBuilders as $builder) {
            $rawContext = $builder->build($type, $context, $rawContext);
        }

        return $rawContext;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUnmarshalContext(string $type, ?Context $context): array
    {
        $context = $context ?? new Context();
        $rawContext = [];

        $rawContext += [
            'hooks' => [
                'property' => (new UnmarshalPropertyHook($this->typeExtractor))(...),
            ],
        ];

        foreach ($this->unmarshalContextBuilders as $builder) {
            $rawContext = $builder->build($type, $context, $rawContext);
        }

        return $rawContext;
    }
}
