<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Hook\ObjectHook;
use Symfony\Component\Marshaller\Hook\PropertyHook;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\NativeContext\GenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\NativeContext\MarshalNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Output\OutputInterface;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

use function Symfony\Component\Marshaller\Native\marshal;
use function Symfony\Component\Marshaller\Native\marshal_generate;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<MarshalNativeContextBuilderInterface>    $marshalNativeContextBuilders
     * @param iterable<GenerationNativeContextBuilderInterface> $generationNativeContextBuilders
     */
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
        private readonly iterable $marshalNativeContextBuilders,
        private readonly iterable $generationNativeContextBuilders,
        private readonly string $cacheDir,
    ) {
    }

    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        $nativeContext = $this->buildMarshalNativeContext($context);
        $type = $nativeContext['type'] ?? get_debug_type($data);

        // if template does not exist, it'll be generated therefore native context must be filled accordingly
        if (!file_exists(sprintf('%s/%s.%s.php', $this->cacheDir, md5($type), $format))) {
            $nativeContext = $this->buildGenerateNativeContext($type, $context, $nativeContext);
        }

        marshal($data, $output->stream(), $format, $nativeContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildGenerateNativeContext($type, $context));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMarshalNativeContext(?Context $context): array
    {
        $context = $context ?? new Context();
        $nativeContext = ['cache_dir' => $this->cacheDir];

        foreach ($this->marshalNativeContextBuilders as $builder) {
            $nativeContext = $builder->build($context, $nativeContext);
        }

        return $nativeContext;
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildGenerateNativeContext(string $type, ?Context $context, array $nativeContext = []): array
    {
        $context = $context ?? new Context();

        $nativeContext += [
            'hooks' => [
                'object' => (new ObjectHook($this->typeExtractor))(...),
                'property' => (new PropertyHook($this->typeExtractor))(...),
                'type' => (new TypeHook($this->typeExtractor))(...),
            ],
        ];

        foreach ($this->generationNativeContextBuilders as $builder) {
            $nativeContext = $builder->build($type, $context, $nativeContext);
        }

        return $nativeContext;
    }
}
