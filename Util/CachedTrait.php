<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Util;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

trait CachedTrait
{
    private readonly CacheItemPoolInterface|null $cacheItemPool;

    /**
     * @var array<string, mixed>
     */
    private array $localCache = [];

    /**
     * @template T of mixed
     *
     * @param callable(): T $getValue
     *
     * @return T
     */
    public function getCached(string $key, callable $getValue): mixed
    {
        if (isset($this->localCache[$key])) {
            return $this->localCache[$key];
        }

        if (null === $this->cacheItemPool) {
            return $this->localCache[$key] = $getValue();
        }

        try {
            $item = $this->cacheItemPool->getItem($key);
        } catch (CacheException) {
            return $this->localCache[$key] = $getValue();
        }

        if ($item->isHit()) {
            return $this->localCache[$key] = $item->get();
        }

        $item->set($value = $getValue());

        $this->cacheItemPool->save($item);

        return $this->localCache[$key] = $value;
    }
}
