<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Cache\MarshallableResolver;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\MarshallableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\MarshallableNotNullableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\MarshallableNullableDummy;

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
     *
     * @param list<list<string>> $expectedArguments
     * @param list<string>       $formats
     */
    public function testWarmUp(array $expectedArguments, array $formats, bool $nullableData): void
    {
        $marshallableResolver = new MarshallableResolver([__DIR__.'/../Fixtures']);
        $marshaller = $this->createMock(MarshallerInterface::class);
        $marshaller
            ->expects($this->exactly(\count($expectedArguments)))
            ->method('generate')
            ->withConsecutive(...$expectedArguments)
            ->willReturn('content');

        (new TemplateCacheWarmer($marshallableResolver, $marshaller, $this->cacheDir, $formats, $nullableData))->warmUp('useless');
    }

    /**
     * @return iterable<array{0: list<array{0: string, 1: string}>, 1: list<string>, 2: bool}>
     */
    public function warmUpDataProvider(): iterable
    {
        yield [
            [
                ['?'.MarshallableNullableDummy::class, 'json'],
                ['?'.MarshallableNullableDummy::class, 'xml'],
                [MarshallableDummy::class, 'json'],
                [MarshallableDummy::class, 'xml'],
                [MarshallableNotNullableDummy::class, 'json'],
                [MarshallableNotNullableDummy::class, 'xml'],
            ],
            ['json', 'xml'],
            false,
        ];

        yield [
            [
                ['?'.MarshallableNullableDummy::class, 'json'],
                ['?'.MarshallableDummy::class, 'json'],
                [MarshallableNotNullableDummy::class, 'json'],
            ],
            ['json'],
            true,
        ];
    }

    public function testGenerateOnlyIfNotExists(): void
    {
        $cacheFilename = sprintf('%s%s%s.json.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5(MarshallableDummy::class));
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        touch($cacheFilename);

        $marshallableResolver = new MarshallableResolver([__DIR__.'/../Fixtures']);
        $marshaller = $this->createMock(MarshallerInterface::class);
        $marshaller
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                ['?'.MarshallableNullableDummy::class, 'json'],
                [MarshallableNotNullableDummy::class, 'json'],
            )
            ->willReturn('content');

        (new TemplateCacheWarmer($marshallableResolver, $marshaller, $this->cacheDir, ['json'], false))->warmUp('useless');
    }
}
