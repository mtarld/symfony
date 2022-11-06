<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\DefaultContextFactory;
use Symfony\Component\Marshaller\Hook\PropertyFormatterHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\PropertyFormatterHookResolver;
use Symfony\Component\Marshaller\Hook\PropertyNameHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\PropertyNameHookResolver;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    public function __construct(
        private readonly string $cacheDir,
        private readonly DefaultContextFactory $defaultContextFactory,
        private readonly PropertyNameHookNativeContextBuilder $propertyNameHookNativeContextBuilder,
        private readonly PropertyFormatterHookNativeContextBuilder $propertyFormatterHookNativeContextBuilder,
    ) {
    }

    public function marshal(object $object, string $format, OutputInterface $output, Context $context = null): void
    {
        $nativeContext = ['cache_path' => $this->cacheDir];

        // compute the context only if the template has to be built
        if (!file_exists(sprintf('%s/%s.php', $this->cacheDir, md5($object::class)))) {
            $nativeContext = $this->computeFullContext($context, $nativeContext, $format, new \ReflectionClass($object));
        }

        marshal($object, $output->stream(), $format, $nativeContext);
    }

    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    private function computeFullContext(?Context $context, array $nativeContext, string $format, \ReflectionClass $class): array
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
            $nativeContext += $option->toNativeContext();
        }

        $nativeContext = $this->propertyNameHookNativeContextBuilder->build($class, $format, $nativeContext);
        $nativeContext = $this->propertyFormatterHookNativeContextBuilder->build($class, $format, $nativeContext);

        // TODO handle arrays

        return $nativeContext;
    }
}
