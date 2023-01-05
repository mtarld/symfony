<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\WarmableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\WarmableNotNullableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\WarmableNullableDummy;

final class TemplateCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().'/symfony_marshaller_test';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider warmUpDataProvider
     */
    public function testWarmUp(array $expectedArguments, array $formats, bool $nullableData): void
    {
        $warmableResolver = new WarmableResolver([__DIR__.'/../Fixtures']);
        $marshaller = $this->createMock(MarshallerInterface::class);
        $marshaller
            ->expects($this->exactly(\count($expectedArguments)))
            ->method('generate')
            ->withConsecutive(...$expectedArguments)
            ->willReturn('content');

        (new TemplateCacheWarmer($warmableResolver, $marshaller, $this->cacheDir, $formats, $nullableData))->warmUp('useless');
    }

    /**
     * @return iterable<array{0: list<array{0: string, 1: string}>, 1: list<string>, 2: bool}>
     */
    public function warmUpDataProvider(): iterable
    {
        yield [
            [
                [WarmableNotNullableDummy::class, 'json'],
                [WarmableNotNullableDummy::class, 'xml'],
                ['?'.WarmableNullableDummy::class, 'json'],
                ['?'.WarmableNullableDummy::class, 'xml'],
                [WarmableDummy::class, 'json'],
                [WarmableDummy::class, 'xml'],
            ],
            ['json', 'xml'],
            false,
        ];

        yield [
            [
                [WarmableNotNullableDummy::class, 'json'],
                ['?'.WarmableNullableDummy::class, 'json'],
                ['?'.WarmableDummy::class, 'json'],
            ],
            ['json'],
            true,
        ];
    }

    public function testGenerateOnlyIfNotExists(): void
    {
        $cacheFilename = sprintf('%s%s%s.json.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5(WarmableDummy::class));
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        touch($cacheFilename);

        $warmableResolver = new WarmableResolver([__DIR__.'/../Fixtures']);
        $marshaller = $this->createMock(MarshallerInterface::class);
        $marshaller
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [WarmableNotNullableDummy::class, 'json'],
                ['?'.WarmableNullableDummy::class, 'json'],
            )
            ->willReturn('content');

        (new TemplateCacheWarmer($warmableResolver, $marshaller, $this->cacheDir, ['json'], false))->warmUp('useless');
    }
}
