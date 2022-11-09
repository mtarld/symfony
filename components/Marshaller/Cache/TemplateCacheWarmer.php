<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\MarshallerInterface;

final class TemplateCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly WarmableResolver $warmableResolver,
        private readonly MarshallerInterface $marshaller,
        private readonly Filesystem $filesystem,
        private readonly string $cacheDir,
    ) {
    }

    public function warmUp(string $cacheDir): void
    {
        foreach ($this->warmableResolver->resolve() as $class) {
            // TODO fixme name should depend on context
            // $this->loadAttributeContexts($class);

            $path = sprintf('%s/%s.php', $this->cacheDir, md5($class->getName()));
            if (!$this->filesystem->exists($path)) {
                // TODO json must be dynamic
                // TODO method with filename
                // $this->warmUpFile()
                $this->filesystem->dumpFile($path, $this->marshaller->generate($class, 'json'));
            }
        }
    }

    public function warmUpFile(string $class, string $format, string $filename): void
    {
        $this->filesystem->dumpFile($path, $this->marshaller->generate($class, 'json'));
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function loadAttributeContexts(\ReflectionClass $class): void
    {
        foreach ($class->getAttributes() as $attribute) {
            if (Warmable::class !== $attribute->getName()) {
                continue;
            }

            foreach ($attribute->newInstance()->contexts as $context) {
                // TODO json must be dynamic
                $this->marshaller->generate($class, 'json', $context);
            }
        }
    }
}
