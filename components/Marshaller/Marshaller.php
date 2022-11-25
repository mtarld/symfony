<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\GenerateNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\PropertyHook;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\Output\OutputInterface;

use function Symfony\Component\Marshaller\Native\marshal;
use function Symfony\Component\Marshaller\Native\marshal_generate;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<MashalNativeContextBuilderInterface>   $marshalNativeContextBuilders
     * @param iterable<GenerateNativeContextBuilderInterface> $generateNativeContextBuilders
     */
    public function __construct(
        private readonly iterable $marshalNativeContextBuilders,
        private readonly iterable $generateNativeContextBuilders,
        private readonly string $cacheDir,
    ) {
    }

    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        $type = $this->getTypeFromData($data);

        // if template does not exist, it'll be generated therefore native context must be filled accordingly
        $nativeContext = file_exists(sprintf('%s/%s.%s.php', $this->cacheDir, md5($type), $format))
            ? $this->buildMarshalNativeContext($type, $context)
            : $this->buildGenerateNativeContext($type, $context);

        marshal($data, $output->stream(), $format, $nativeContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildGenerateNativeContext($type, $context));
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildMarshalNativeContext(string $type, ?Context $context): array
    {
        $context = $context ?? new Context();
        $nativeContext = [];

        foreach ($this->marshalNativeContextBuilders as $builder) {
            $nativeContext = $builder->buildMarshalNativeContext($type, $context, $nativeContext);
        }

        return $nativeContext;
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildGenerateNativeContext(string $type, ?Context $context): array
    {
        $context = $context ?? new Context();
        $nativeContext = [
            'hooks' => [
                'property' => (new PropertyHook())(...),
                'type' => (new TypeHook())(...),
            ],
        ];

        foreach ($this->generateNativeContextBuilders as $builder) {
            $nativeContext = $builder->buildGenerateNativeContext($type, $context, $nativeContext);
        }

        return $nativeContext;
    }

    private function getTypeFromData(mixed $data): string
    {
        if (is_object($data)) {
            return $data::class;
        }

        $builtinType = strtolower(gettype($data));
        $builtinType = ['integer' => 'int', 'boolean' => 'bool', 'double' => 'float'][$builtinType] ?? $builtinType;

        return $builtinType;
    }
}
