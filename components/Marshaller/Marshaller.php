<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilderInterface;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<NativeContextBuilderInterface> $nativeContextBuilders
     */
    public function __construct(
        private readonly iterable $nativeContextBuilders,
        private readonly string $cacheDir,
    ) {
    }

    // TODO type context
    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        $type = $this->getType($data, $context);
        $templateExists = file_exists(sprintf('%s/%s.%s.php', $this->cacheDir, $type, $format));

        // Enforce generation with a complete context
        if (!$templateExists) {
            $this->generate($type, $format, $context);
        }

        marshal($data, $output->stream(), $format, ['cache_path' => $this->cacheDir]);
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        $nativeContext = ['cache_path' => $this->cacheDir];

        foreach ($this->nativeContextBuilders as $builder) {
            $nativeContext = $builder->build($format, $nativeContext);
        }

        // TODO
        // $nativeContext = $this->mergeWithContext($context, $nativeContext);

        return marshal_generate($type, $format, $nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function mergeWithContext(?Context $context, array $nativeContext): array
    {
        // $defaultContext = $this->defaultContextFactory->create();
        //
        // if (null !== $context) {
        //     foreach ($defaultContext as $defaultOption) {
        //         if (!$context->has($defaultOption::class)) {
        //             $context = $context->with($defaultOption);
        //         }
        //     }
        // }
        //
        // foreach ($context ?? $defaultContext as $option) {
        //     $nativeContext = $option->mergeNativeContext($nativeContext);
        // }

        if (null === $context) {
            return $nativeContext;
        }

        foreach ($context as $option) {
            $nativeContext = $option->mergeNativeContext($nativeContext);
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
        // TODO
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
