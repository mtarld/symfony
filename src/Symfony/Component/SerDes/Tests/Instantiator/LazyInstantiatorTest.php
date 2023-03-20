<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Instantiator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Instantiator\LazyInstantiator;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

class LazyInstantiatorTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_ser_des_lazy_object', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testCreateLazyGhost()
    {
        $ghost = (new LazyInstantiator($this->cacheDir))(new \ReflectionClass(ClassicDummy::class), [], []);

        $this->assertArrayHasKey(sprintf("\0%sGhost\0lazyObjectState", preg_replace('/\\\\/', '', ClassicDummy::class)), (array) $ghost);
    }

    public function testCreateCacheFile()
    {
        (new LazyInstantiator($this->cacheDir))(new \ReflectionClass(DummyWithFormatterAttributes::class), [], []);

        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }
}
