<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\NativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\NullableDataOption;
use Symfony\Component\Marshaller\Hook\PropertyHook;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\Output\OutputInterface;

use function Symfony\Component\Marshaller\Native\marshal;
use function Symfony\Component\Marshaller\Native\marshal_generate;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<NativeContextBuilderInterface> $nativeContextBuilders
     */
    public function __construct(
        private readonly iterable $nativeContextBuilders,
    ) {
    }

    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        $type = $this->getTypeFromData($data, null !== $context?->get(NullableDataOption::class));

        marshal($data, $output->stream(), $format, $this->buildNativeContext($type, $format, $context));
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildNativeContext($type, $format, $context));
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildNativeContext(string $type, string $format, ?Context $context): array
    {
        $context = $context ?? new Context();
        $nativeContext = [];

        foreach ($this->nativeContextBuilders as $builder) {
            $nativeContext = $builder->build($type, $format, $context, $nativeContext);
        }

        $nativeContext['hooks']['property'] = (new PropertyHook())(...);
        $nativeContext['hooks']['type'] = (new TypeHook())(...);

        return $nativeContext;
    }

    private function getTypeFromData(mixed $data, bool $nullable): string
    {
        $nullablePrefix = $nullable ? '?' : '';

        if (is_object($data)) {
            return $nullablePrefix.$data::class;
        }

        $builtinType = strtolower(gettype($data));
        $builtinType = ['integer' => 'int', 'boolean' => 'bool', 'double' => 'float'][$builtinType] ?? $builtinType;

        return $nullablePrefix.$builtinType;
    }
}
