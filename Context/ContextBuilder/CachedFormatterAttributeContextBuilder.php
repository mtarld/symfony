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
final class CachedFormatterAttributeContextBuilder implements ContextBuilderInterface
{
    use CachedTrait;
    private const CACHE_KEY = 'marshaller.context.formatter_attribute';

    public function __construct(
        private readonly ContextBuilderInterface $contextBuilder,
        private readonly CacheItemPoolInterface|null $cacheItemPool = null,
    ) {
    }

    public function buildMarshalContext(array $context, bool $willGenerateTemplate): array
    {
        $cachedContext = $this->getCached(self::CACHE_KEY, fn (): array => $this->contextBuilder->buildMarshalContext($context, $willGenerateTemplate));

        if (isset($cachedContext['_symfony']['marshal']['property_formatter'])) {
            $context['_symfony']['marshal']['property_formatter'] = $cachedContext['_symfony']['marshal']['property_formatter'];
        }

        return $context;
    }

    public function buildUnmarshalContext(array $context): array
    {
        $cachedContext = $this->getCached(self::CACHE_KEY, fn (): array => $this->contextBuilder->buildUnmarshalContext($context));

        if (isset($cachedContext['_symfony']['unmarshal']['property_formatter'])) {
            $context['_symfony']['unmarshal']['property_formatter'] = $cachedContext['_symfony']['unmarshal']['property_formatter'];
        }

        return $context;
    }
}
