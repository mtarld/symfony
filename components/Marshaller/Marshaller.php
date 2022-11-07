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
        private readonly string $cacheDir,
        private readonly DefaultContextFactory $defaultContextFactory,
        private readonly iterable $marshalNativeContextBuilders,
        private readonly iterable $templateGenerationNativeContextBuilders,
    ) {
    }

    public function marshal(object $object, string $format, OutputInterface $output, Context $context = null): void
    {
        $nativeContext = ['cache_path' => $this->cacheDir];

        $reflectionClass = new \ReflectionClass($object);

        if (!file_exists(sprintf('%s/%s.php', $this->cacheDir, md5($object::class)))) {
            $this->validateObject($reflectionClass);

            foreach ($this->templateGenerationNativeContextBuilders as $builder) {
                $nativeContext = $builder->forTemplateGeneration($reflectionClass, $format, $nativeContext);
            }

            $nativeContext = $this->mergeWithContext($context, $nativeContext);
        } else {
            foreach ($this->marshalNativeContextBuilders as $builder) {
                $nativeContext = $builder->forMarshal($reflectionClass, $format, $nativeContext);
            }
        }

        marshal($object, $output->stream(), $format, $nativeContext);
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

    private function validateObject(\ReflectionClass $class): void
    {
        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new \RuntimeException(sprintf('"%s::$%s" must be public', $class->getName(), $property->getName()));
            }
        }
    }
}
