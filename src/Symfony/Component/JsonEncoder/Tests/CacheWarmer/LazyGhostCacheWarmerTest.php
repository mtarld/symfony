<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\JsonEncoder\CacheWarmer\LazyGhostCacheWarmer;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;

class LazyGhostCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_encoder_lazy_ghost', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testWarmUpLazyGhost()
    {
        (new LazyGhostCacheWarmer([ClassicDummy::class], $this->cacheDir))->warmUp('useless');

        $this->assertSame(
            array_map(fn (string $c): string => sprintf('%s/%s.php', $this->cacheDir, hash('xxh128', $c)), [ClassicDummy::class]),
            glob($this->cacheDir.'/*'),
        );
    }
}
