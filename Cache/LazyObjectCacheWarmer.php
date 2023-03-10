<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class LazyObjectCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly MarshallableResolverInterface $marshallableResolver,
        private readonly string $cacheDir,
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        foreach ($this->marshallableResolver->resolve() as $class => $_) {
            if (file_exists($path = sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5($class)))) {
                continue;
            }

            file_put_contents($path, sprintf(
                'class %s%s',
                sprintf('%sGhost', preg_replace('/\\\\/', '', $class)),
                ProxyHelper::generateLazyGhost(new \ReflectionClass($class)),
            ));
        }

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
