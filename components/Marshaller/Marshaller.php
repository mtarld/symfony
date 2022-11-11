<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\GenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\MarshalNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<GenerationNativeContextBuilderInterface> $generationNativeContextBuilders
     * @param iterable<MarshalNativeContextBuilderInterface>    $marshalNativeContextBuilders
     */
    public function __construct(
        private readonly iterable $generationNativeContextBuilders,
        private readonly iterable $marshalNativeContextBuilders,
        private readonly string $cacheDir,
    ) {
    }

    // TODO type context
    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        $type = $this->getType($data, $context);
        $templateExists = file_exists(sprintf('%s/%s.%s.php', $this->cacheDir, md5($type), $format));
        $nativeContext = $templateExists ? $this->buildMarshalNativeContext($data, $format, $context) : $this->buildGenerationNativeContext($type, $format, $context);

        marshal($data, $output->stream(), $format, $nativeContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildGenerationNativeContext($type, $format, $context));
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildGenerationNativeContext(string $type, string $format, Context $context = null): array
    {
        $context = $context ?? new Context();
        $nativeContext = ['cache_path' => $this->cacheDir];

        foreach ($this->generationNativeContextBuilders as $builder) {
            $nativeContext = $builder->forGeneration($type, $format, $context, $nativeContext);
        }

        return $nativeContext;
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildMarshalNativeContext(mixed $data, string $format, Context $context = null): array
    {
        $context = $context ?? new Context();
        $nativeContext = ['cache_path' => $this->cacheDir];

        foreach ($this->marshalNativeContextBuilders as $builder) {
            $nativeContext = $builder->forMarshal($data, $format, $context, $nativeContext);
        }

        return $nativeContext;
    }

    // TODO move me into the polyfill
    private function validateClass(\ReflectionClass $class): void
    {
        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new \RuntimeException(sprintf('"%s::$%s" must be public', $class->getName(), $property->getName()));
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getType(mixed $data, ?Context $context): string
    {
        // TODO nullable option
        // $nullablePrefix = true === ($context['nullable_data'] ?? false) ? '?' : '';
        $nullablePrefix = '';

        if (is_object($data)) {
            return $nullablePrefix.$data::class;
        }

        $type = strtolower(gettype($data));

        $typesMap = [
            'integer' => 'int',
            'boolean' => 'bool',
            'double' => 'float',
        ];

        return $nullablePrefix.($typesMap[$type] ?? $type);
    }
}
