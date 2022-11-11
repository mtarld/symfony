<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\Filesystem\Filesystem;
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
        private readonly Filesystem $filesystem,
        private readonly string $cacheDir,
        private readonly array $formats,
        private readonly bool $nullableData,
    ) {
    }

    public function warmUp(string $cacheDir): void
    {
        foreach ($this->warmableResolver->resolve() as [$class, $attribute]) {
            foreach ($this->formats as $format) {
                $this->warmClass($class, $attribute, $format);
            }
        }
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
        if ($this->filesystem->exists($path)) {
            return;
        }

        if ($attribute->nullable ?? $this->nullableData) {
            $class = '?'.$class;
        }

        $this->filesystem->dumpFile($path, $this->marshaller->generate($class, $format));
    }
}
