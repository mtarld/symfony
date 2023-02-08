<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Generation;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CachedFormatterAttributeContextBuilder implements GenerationContextBuilderInterface
{
    use CachedTrait;

    public function __construct(
        private readonly GenerationContextBuilderInterface $contextBuilder,
        CacheItemPoolInterface $cacheItemPool,
    ) {
        $this->cacheItemPool = $cacheItemPool;
    }

    public function build(string $type, Context $context, array $rawContext): array
    {
        $cachedRawContext = $this->getCached('marshaller.raw_context.generation.formatter_attribute', fn () => $this->contextBuilder->build($type, $context, []));

        if (isset($cachedRawContext['_symfony']['marshal']['property_formatter'])) {
            $rawContext['_symfony']['marshal']['property_formatter'] = $cachedRawContext['_symfony']['marshal']['property_formatter'];
        }

        return $rawContext;
    }
}
