<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Generates lazy ghost {@see Symfony\Component\VarExporter\LazyGhostTrait}
 * PHP files for $encodable types.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class LazyGhostCacheWarmer extends CacheWarmer
{
    /**
     * @param list<class-string> $encodableClassNames
     */
    public function __construct(
        private readonly array $encodableClassNames,
        private readonly string $lazyGhostCacheDir,
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        if (!file_exists($this->lazyGhostCacheDir)) {
            mkdir($this->lazyGhostCacheDir, recursive: true);
        }

        foreach ($this->encodableClassNames as $className) {
            $this->warmClassLazyGhost($className);
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @param class-string $className
     */
    private function warmClassLazyGhost(string $className): void
    {
        $path = sprintf('%s%s%s.php', $this->lazyGhostCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className));

        $this->writeCacheFile($path, sprintf(
            'class %s%s',
            sprintf('%sGhost', preg_replace('/\\\\/', '', $className)),
            ProxyHelper::generateLazyGhost(new \ReflectionClass($className)),
        ));
    }
}
