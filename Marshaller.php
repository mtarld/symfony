<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\MarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\UnmarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ObjectHook;
use Symfony\Component\Marshaller\Hook\PropertyHook;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\Stream\StreamInterface;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

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
            $rawContext = $this->buildGenerateContext($type, $context, $rawContext);
        }

        marshal($data, $output->stream(), $format, $rawContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildGenerateContext($type, $context));
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
    private function buildGenerateContext(string $type, ?Context $context, array $rawContext = []): array
    {
        $context = $context ?? new Context();

        $rawContext += [
            'hooks' => [
                'object' => (new ObjectHook($this->typeExtractor))(...),
                'property' => (new PropertyHook($this->typeExtractor))(...),
                'type' => (new TypeHook($this->typeExtractor))(...),
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

        foreach ($this->unmarshalContextBuilders as $builder) {
            $rawContext = $builder->build($type, $context, $rawContext);
        }

        return $rawContext;
    }
}
