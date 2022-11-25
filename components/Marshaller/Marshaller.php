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

        /** @var TypeOption|null $typeOption */
        if ($typeOption = $context?->get(TypeOption::class)) {
            $nativeContext['type'] = $typeOption->type;
        }

        marshal($data, $output->stream(), $format, $nativeContext);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        $context = $context ?? new Context();
        $nativeContext = [
            'cache_dir' => $this->cacheDir,
            'hooks' => [
                'property' => (new PropertyHook())(...),
                'type' => (new TypeHook())(...),
            ],
        ];

        foreach ($this->marshalGenerateNativeContextBuilders as $builder) {
            $nativeContext = $builder->build($type, $context, $nativeContext);
        }

        return marshal_generate($type, $format, $nativeContext);
    }
}
