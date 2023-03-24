<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\SerDes\Context\ContextBuilderInterface;
use Symfony\Component\SerDes\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
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

    public function buildSerializeContext(array $context, bool $willGenerateTemplate): array
    {
        $cachedContextPart = $this->getCached($this->cacheKey.'_serialize', fn () => $this->contextBuilder->buildSerializeContext($context, $willGenerateTemplate)['_symfony'][$this->contextKey] ?? null);

        if (null !== $cachedContextPart) {
            $context['_symfony'][$this->contextKey] = $cachedContextPart;
        }

        return $context;
    }

    public function buildDeserializeContext(array $context): array
    {
        $cachedContextPart = $this->getCached($this->cacheKey.'_deserialize', fn () => $this->contextBuilder->buildDeserializeContext($context)['_symfony'][$this->contextKey] ?? null);

        if (null !== $cachedContextPart) {
            $context['_symfony'][$this->contextKey] = $cachedContextPart;
        }

        return $context;
    }
}
