<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
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
        $cachedContext = $this->getCached($this->cacheKey.'_marshal', fn () => $this->contextBuilder->buildMarshalContext($context, $willGenerateTemplate));

        if (isset($cachedContext['_symfony'][$this->contextKey])) {
            $context['_symfony'][$this->contextKey] = $cachedContext['_symfony'][$this->contextKey];
        }

        return $context;
    }

    public function buildUnmarshalContext(array $context): array
    {
        $cachedContext = $this->getCached($this->cacheKey.'_unmarshal', fn (): array => $this->contextBuilder->buildUnmarshalContext($context));

        if (isset($cachedContext['_symfony'][$this->contextKey])) {
            $context['_symfony'][$this->contextKey] = $cachedContext['_symfony'][$this->contextKey];
        }

        return $context;
    }
}
