<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
final class CachedContextBuilder implements ContextBuilderInterface
{
    use CachedTrait;

    public function __construct(
        private readonly ContextBuilderInterface $contextBuilder,
        private readonly string $contextKey,
        private readonly string $cacheKey,
        private readonly CacheItemPoolInterface|null $cacheItemPool = null,
    ) {
    }

    public function buildMarshalContext(array $context, bool $willGenerateTemplate): array
    {
        $cachedContextPart = $this->getCached($this->cacheKey.'_marshal', fn () => $this->contextBuilder->buildMarshalContext($context, $willGenerateTemplate)['_symfony'][$this->contextKey] ?? null);

        if (null !== $cachedContextPart) {
            $context['_symfony'][$this->contextKey] = $cachedContextPart;
        }

        return $context;
    }

    public function buildUnmarshalContext(array $context): array
    {
        $cachedContextPart = $this->getCached($this->cacheKey.'_unmarshal', fn () => $this->contextBuilder->buildUnmarshalContext($context)['_symfony'][$this->contextKey] ?? null);

        if (null !== $cachedContextPart) {
            $context['_symfony'][$this->contextKey] = $cachedContextPart;
        }

        return $context;
    }
}
