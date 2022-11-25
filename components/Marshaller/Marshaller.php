<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\GenerateNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Hook\PropertyHook;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\Output\OutputInterface;

use function Symfony\Component\Marshaller\Native\marshal;
use function Symfony\Component\Marshaller\Native\marshal_generate;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<GenerateNativeContextBuilderInterface> $marshalGenerateNativeContextBuilders
     */
    public function __construct(
        private readonly iterable $marshalGenerateNativeContextBuilders,
        private readonly string $cacheDir,
    ) {
    }

    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        $nativeContext = ['cache_dir' => $this->cacheDir];

        $type = $this->getTypeFromData($data);

        /** @var TypeOption|null $typeOption */
        if ($typeOption = $context?->get(TypeOption::class)) {
            $type = $nativeContext['type'] = $typeOption->type;
        }

        // if template does not exist, it'll be generated therefore native context must be filled accordingly
        if (!file_exists(sprintf('%s/%s.%s.php', $this->cacheDir, md5($type), $format))) {
            $nativeContext = $this->buildMarshalGenerateNativeContext($type, $context, $nativeContext);
        }

        marshal($data, $output->stream(), $format, $nativeContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildMarshalGenerateNativeContext($type, $context));
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildMarshalGenerateNativeContext(string $type, ?Context $context, array $nativeContext = []): array
    {
        $context = $context ?? new Context();

        $nativeContext += [
            'hooks' => [
                'property' => (new PropertyHook())(...),
                'type' => (new TypeHook())(...),
            ],
        ];

        foreach ($this->marshalGenerateNativeContextBuilders as $builder) {
            $nativeContext = $builder->build($type, $context, $nativeContext);
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
