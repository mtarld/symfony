<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\MarshallerInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class TemplateCacheWarmer implements CacheWarmerInterface
{
    /**
     * @param list<string> $formats
     */
    public function __construct(
        private readonly MarshallableResolver $marshallableResolver,
        private readonly MarshallerInterface $marshaller,
        private readonly string $cacheDir,
        private readonly array $formats,
        private readonly bool $nullableData,
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        foreach ($this->marshallableResolver->resolve() as $class => $attribute) {
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
    private function warmClass(string $class, Marshallable $attribute, string $format): void
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
