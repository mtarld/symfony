<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\MarshallerInterface;

final class TemplateCacheWarmer implements CacheWarmerInterface
{
    /**
     * @param list<string> $formats
     */
    public function __construct(
        private readonly WarmableResolver $warmableResolver,
        private readonly MarshallerInterface $marshaller,
        private readonly string $cacheDir,
        private readonly array $formats,
        private readonly bool $nullableData,
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        foreach ($this->warmableResolver->resolve() as $class => $attribute) {
            foreach ($this->formats as $format) {
                $this->warmClass($class, $attribute, $format);
            }
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @param class-string $class
     */
    private function warmClass(string $class, Warmable $attribute, string $format): void
    {
        $path = sprintf('%s/%s.%s.php', $this->cacheDir, md5($class), $format);
        if (file_exists($path)) {
            return;
        }

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        if ($attribute->nullable ?? $this->nullableData) {
            $class = '?'.$class;
        }

        file_put_contents($path, $this->marshaller->generate($class, $format));
    }
}
