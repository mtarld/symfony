<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerDesCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;

class SerDesCacheWarmerTest extends TestCase
{
    private string $templateCacheDir;
    private string $lazyObjectCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCacheDir = sprintf('%s/symfony_ser_des_template', sys_get_temp_dir());

        if (is_dir($this->templateCacheDir)) {
            array_map('unlink', glob($this->templateCacheDir.'/*'));
            rmdir($this->templateCacheDir);
        }

        $this->lazyObjectCacheDir = sprintf('%s/symfony_ser_des_lazy_object', sys_get_temp_dir());

        if (is_dir($this->lazyObjectCacheDir)) {
            array_map('unlink', glob($this->lazyObjectCacheDir.'/*'));
            rmdir($this->lazyObjectCacheDir);
        }
    }

    public function testWarmUpTemplate()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([ClassicDummy::class]));

        (new SerDesCacheWarmer($serializableResolver, $this->templateCacheDir, $this->lazyObjectCacheDir, ['json'], 32))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.json.php', $this->templateCacheDir, hash('xxh128', $c)), [ClassicDummy::class]);

        $this->assertSame($expectedTemplates, glob($this->templateCacheDir.'/*'));
    }

    public function testWarmUpLazyObject()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([ClassicDummy::class]));

        (new SerDesCacheWarmer($serializableResolver, $this->templateCacheDir, $this->lazyObjectCacheDir, ['json'], 32))->warmUp('useless');

        $expectedLazyObjects = array_map(fn (string $c): string => sprintf('%s/%s.php', $this->lazyObjectCacheDir, hash('xxh128', $c)), [ClassicDummy::class]);

        $this->assertSame($expectedLazyObjects, glob($this->lazyObjectCacheDir.'/*'));
    }
}
