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
use Symfony\Component\Marshaller\MarshallerInterface;

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
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

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
                ['MarshallableDummy', 'json'],
                ['MarshallableDummy', 'xml'],
                ['?MarshallableNullableDummy', 'json'],
                ['?MarshallableNullableDummy', 'xml'],
                ['MarshallableNotNullableDummy', 'json'],
                ['MarshallableNotNullableDummy', 'xml'],
            ],
            ['json', 'xml'],
            false,
        ];

        yield [
            [
                ['?MarshallableDummy', 'json'],
                ['?MarshallableNullableDummy', 'json'],
                ['MarshallableNotNullableDummy', 'json'],
            ],
            ['json'],
            true,
        ];
    }

    public function testGenerateOnlyIfNotExists(): void
    {
        $cacheFilename = sprintf('%s%s%s.json.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5('MarshallableDummy'));
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        touch($cacheFilename);

        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $marshaller = $this->createMock(MarshallerInterface::class);
        $marshaller
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                ['?MarshallableNullableDummy', 'json'],
                ['MarshallableNotNullableDummy', 'json'],
            )
            ->willReturn('content');

        (new TemplateCacheWarmer($marshallableResolver, $marshaller, $this->cacheDir, ['json'], false))->warmUp('useless');
    }

    /**
     * @return \Generator<string, Marshallable>
     */
    private function getMarshallable(): \Generator
    {
        yield 'MarshallableDummy' => new Marshallable();
        yield 'MarshallableNullableDummy' => new Marshallable(true);
        yield 'MarshallableNotNullableDummy' => new Marshallable(false);
    }
}
