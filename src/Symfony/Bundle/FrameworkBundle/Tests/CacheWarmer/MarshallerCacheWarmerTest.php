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

use Symfony\Bundle\FrameworkBundle\CacheWarmer\MarshallerCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithQuotes;

class MarshallerCacheWarmerTest extends TestCase
{
    private string $templateCacheDir;
    private string $lazyObjectCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCacheDir = sprintf('%s/symfony_marshaller_template', sys_get_temp_dir());

        if (is_dir($this->templateCacheDir)) {
            array_map('unlink', glob($this->templateCacheDir.'/*'));
            rmdir($this->templateCacheDir);
        }

        $this->lazyObjectCacheDir = sprintf('%s/symfony_marshaller_lazy_object', sys_get_temp_dir());

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
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn(new \ArrayIterator([
            ClassicDummy::class => new Marshallable(),
            DummyWithQuotes::class => new Marshallable(true),
            DummyWithMethods::class => new Marshallable(false),
        ]));

        (new MarshallerCacheWarmer($marshallableResolver, [], $this->templateCacheDir, $this->lazyObjectCacheDir, ['json'], $nullableData))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.json.php', $this->templateCacheDir, md5($c)), $expectedClasses);

        $this->assertSame($expectedTemplates, glob($this->templateCacheDir.'/*'));
    }

    /**
     * @return iterable<array{0: list<string>, 1: bool}>
     */
    public function warmUpTemplateDataProvider(): iterable
    {
        yield [[ClassicDummy::class, DummyWithMethods::class, '?'.DummyWithQuotes::class], false];
        yield [['?'.ClassicDummy::class, DummyWithMethods::class, '?'.DummyWithQuotes::class], true];
    }

    public function testWarmUpLazyObject()
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn(new \ArrayIterator([
            ClassicDummy::class => new Marshallable(),
            DummyWithQuotes::class => new Marshallable(),
            DummyWithMethods::class => new Marshallable(),
        ]));

        (new MarshallerCacheWarmer($marshallableResolver, [], $this->templateCacheDir, $this->lazyObjectCacheDir, ['json'], false))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.php', $this->lazyObjectCacheDir, md5($c)), [
            ClassicDummy::class,
            DummyWithQuotes::class,
            DummyWithMethods::class,
        ]);

        $this->assertSame($expectedTemplates, glob($this->lazyObjectCacheDir.'/*'));
    }
}
