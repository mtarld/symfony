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
use Symfony\Component\SerDes\Attribute\Serializable;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithQuotes;

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

    /**
     * @dataProvider warmUpTemplateDataProvider
     *
     * @param list<string> $expectedClasses
     */
    public function testWarmUpTemplate(array $expectedClasses, bool $nullableData)
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            ClassicDummy::class => new Serializable(),
            DummyWithQuotes::class => new Serializable(true),
            DummyWithMethods::class => new Serializable(false),
        ]));

        (new SerDesCacheWarmer($serializableResolver, [], $this->templateCacheDir, $this->lazyObjectCacheDir, ['json'], $nullableData))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.json.php', $this->templateCacheDir, md5($c)), $expectedClasses);

        $this->assertSame($expectedTemplates, glob($this->templateCacheDir.'/*'));
    }

    /**
     * @return iterable<array{0: list<string>, 1: bool}>
     */
    public static function warmUpTemplateDataProvider(): iterable
    {
        yield [[DummyWithMethods::class, '?'.DummyWithQuotes::class, ClassicDummy::class], false];
        yield [[DummyWithMethods::class, '?'.DummyWithQuotes::class, '?'.ClassicDummy::class], true];
    }

    public function testWarmUpLazyObject()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            ClassicDummy::class => new Serializable(),
            DummyWithQuotes::class => new Serializable(),
            DummyWithMethods::class => new Serializable(),
        ]));

        (new SerDesCacheWarmer($serializableResolver, [], $this->templateCacheDir, $this->lazyObjectCacheDir, ['json'], false))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.php', $this->lazyObjectCacheDir, md5($c)), [
            DummyWithQuotes::class,
            DummyWithMethods::class,
            ClassicDummy::class,
        ]);

        $this->assertSame($expectedTemplates, glob($this->lazyObjectCacheDir.'/*'));
    }
}
