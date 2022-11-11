<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\GenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\MarshalNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\NullableDataOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
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
        $nativeContext = [];

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
        $nativeContext = [];

        foreach ($this->marshalNativeContextBuilders as $builder) {
            $nativeContext = $builder->forMarshal($data, $format, $context, $nativeContext);
        }

        return $nativeContext;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getType(mixed $data, ?Context $context): string
    {
        if (null !== $context && null !== ($typeOption = $context->get(TypeOption::class))) {
            return $typeOption->type;
        }

        $nullablePrefix = (null !== $context && null !== $context->get(NullableDataOption::class)) ? '?' : '';

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
