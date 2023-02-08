<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\UnmarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CachedNameAttributeContextBuilder implements UnmarshalContextBuilderInterface
{
    use CachedTrait;

    public function __construct(
        private readonly UnmarshalContextBuilderInterface $contextBuilder,
        CacheItemPoolInterface $cacheItemPool,
    ) {
        $this->cacheItemPool = $cacheItemPool;
    }

    public function build(string $type, Context $context, array $rawContext): array
    {
        $cachedRawContext = $this->getCached('marshaller.raw_context.unmarshal.name_attribute', fn () => $this->contextBuilder->build($type, $context, []));

        if (isset($cachedRawContext['_symfony']['unmarshal']['property_name'])) {
            $rawContext['_symfony']['unmarshal']['property_name'] = $cachedRawContext['_symfony']['unmarshal']['property_name'];
        }

        return $rawContext;
    }
}
