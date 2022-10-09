<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\Context\ContextDeclinationResolver;
use Symfony\Component\Marshaller\Template\TemplateLoader;

final class TemplateCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly WarmableResolver $warmableResolver,
        private readonly TemplateLoader $templateLoader,
        private readonly ContextDeclinationResolver $contextDeclinationResolver,
    ) {
    }

    public function warmUp(string $cacheDir): void
    {
        foreach ($this->warmableResolver->resolve() as $class) {
            $this->loadDeclinationContexts($class);
            $this->loadEnforcedContexts($class);
        }
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function loadDeclinationContexts(\ReflectionClass $class): void
    {
        $contexts = $this->contextDeclinationResolver->resolve($class);

        foreach ($contexts as $context) {
            $this->templateLoader->save($class, $context);
        }
    }

    private function loadEnforcedContexts(\ReflectionClass $class): void
    {
        foreach ($class->getAttributes() as $attribute) {
            if (Warmable::class !== $attribute->getName()) {
                continue;
            }

            foreach ($attribute->newInstance()->enforcedContexts as $context) {
                $this->templateLoader->save($class, $context);
            }
        }
    }
}
