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
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithQuotes;

final class TemplateCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_marshaller_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider warmUpDataProvider
     *
     * @param list<string> $expectedClasses
     */
    public function testWarmUp(array $expectedClasses, bool $nullableData): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn(new \ArrayIterator([
            ClassicDummy::class => new Marshallable(),
            DummyWithQuotes::class => new Marshallable(true),
            DummyWithMethods::class => new Marshallable(false),
        ]));

        (new TemplateCacheWarmer($marshallableResolver, [], $this->cacheDir, ['json'], $nullableData))->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.json.php', $this->cacheDir, md5($c)), $expectedClasses);

        $this->assertSame($expectedTemplates, glob($this->cacheDir.'/*'));
    }

    /**
     * @return iterable<array{0: list<string>, 1: bool}>
     */
    public function warmUpDataProvider(): iterable
    {
        yield [[ClassicDummy::class, DummyWithMethods::class, '?'.DummyWithQuotes::class], false];
        yield [['?'.ClassicDummy::class, DummyWithMethods::class, '?'.DummyWithQuotes::class], true];
    }
}
