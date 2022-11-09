<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\DefaultContextFactory;
use Symfony\Component\Marshaller\Context\MarshalNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<MarshalNativeContextBuilderInterface>            $marshalNativeContextBuilders
     * @param iterable<TemplateGenerationNativeContextBuilderInterface> $templateGenerationNativeContextBuilders
     */
    public function __construct(
        private readonly DefaultContextFactory $defaultContextFactory,
        private readonly iterable $marshalNativeContextBuilders,
        private readonly iterable $templateGenerationNativeContextBuilders,
        private readonly string $cacheDir,
    ) {
    }

    public function marshal(object $object, string $format, OutputInterface $output, Context $context = null): void
    {
        $class = new \ReflectionClass($object);
        $templateExists = file_exists(sprintf('%s/%s.php', $this->cacheDir, md5($object::class)));
        if (!$templateExists) {
            $this->validateClass($class);
        }

        $nativeContext = $templateExists
            ? $this->buildMarshalNativeContext($class, $format, $context)
            : $this->buildTemplateGenerationNativeContext($class, $format, $context);

        marshal($object, $output->stream(), $format, $nativeContext);
    }

    public function generate(\ReflectionClass $class, string $format, Context $context = null): string
    {
        $this->validateClass($class);

        return marshal_generate($class, $format, $this->buildTemplateGenerationNativeContext($class, $format, $context));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTemplateGenerationNativeContext(\ReflectionClass $class, string $format, ?Context $context): array
    {
        $nativeContext = ['cache_path' => $this->cacheDir];

        foreach ($this->templateGenerationNativeContextBuilders as $builder) {
            $nativeContext = $builder->forTemplateGeneration($class, $format, $nativeContext);
        }

        return $this->mergeWithContext($context, $nativeContext);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMarshalNativeContext(\ReflectionClass $class, string $format, ?Context $context): array
    {
        $nativeContext = ['cache_path' => $this->cacheDir];

        foreach ($this->marshalNativeContextBuilders as $builder) {
            $nativeContext = $builder->forMarshal($class, $format, $nativeContext);
        }

        return $nativeContext;
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function mergeWithContext(?Context $context, array $nativeContext): array
    {
        $defaultContext = $this->defaultContextFactory->create();

        if (null !== $context) {
            foreach ($defaultContext as $defaultOption) {
                if (!$context->has($defaultOption::class)) {
                    $context = $context->with($defaultOption);
                }
            }
        }

        foreach ($context ?? $defaultContext as $option) {
            $nativeContext = $option->mergeNativeContext($nativeContext);
        }

        return $nativeContext;
    }

    private function validateClass(\ReflectionClass $class): void
    {
        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new \RuntimeException(sprintf('"%s::$%s" must be public', $class->getName(), $property->getName()));
            }
        }
    }
}
