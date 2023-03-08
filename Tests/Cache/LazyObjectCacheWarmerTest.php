<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\Cache\LazyObjectCacheWarmer;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithQuotes;

final class LazyObjectCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_marshaller_lazy_object', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testWarmUp(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        (new LazyObjectCacheWarmer($marshallableResolver, $this->cacheDir))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.php', $this->cacheDir, md5($c)), [
            ClassicDummy::class,
            DummyWithQuotes::class,
            DummyWithMethods::class,
        ]);

        $this->assertSame($expectedTemplates, glob($this->cacheDir.'/*'));
    }

    /**
     * @return \Generator<string, Marshallable>
     */
    private function getMarshallable(): \Generator
    {
        yield ClassicDummy::class => new Marshallable();
        yield DummyWithQuotes::class => new Marshallable(true);
        yield DummyWithMethods::class => new Marshallable(false);
    }
}
