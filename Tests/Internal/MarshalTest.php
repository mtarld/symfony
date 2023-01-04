<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithQuotes;

use function Symfony\Component\Marshaller\marshal;

final class MarshalTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'symfony_marshaller';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.\DIRECTORY_SEPARATOR.'*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider marshalContentDataProvider
     */
    public function testMarshalContent(mixed $data, ?string $type = null): void
    {
        $this->assertSame(json_encode($data), $this->marshalAsString($data, 'json', (null !== $type) ? ['type' => $type] : []));
    }

    /**
     * @return iterable<array{0: mixed, 1: string?}>
     */
    public function marshalContentDataProvider(): iterable
    {
        yield [1];
        yield ['1'];
        yield ['foo'];
        yield [null];
        yield [.01];
        yield [false];
        yield [new ClassicDummy()];
        yield [new DummyWithQuotes()];
        yield [[1, 2, 3], 'array<int, int>'];
        yield [[1, 2, 3.12], 'array<int, int|float>'];
        yield [[true, false, true], 'iterable<int, bool>'];
        yield [[false, null], 'array<int, ?bool>'];
        yield [['a' => 'b', 'c' => 'd'], 'array<string, string>'];
        yield [['a' => false, 'b' => 'd'], 'array<string, string|bool>'];
        yield [['"a"' => '"b"'], 'array<string, string>'];
        yield [['a' => 1, 'b' => null], 'iterable<string, ?string>'];
        yield [[1, 2.12, new ClassicDummy()], sprintf('array<int, int|float|%s>', ClassicDummy::class)];
    }

    public function testMarshalContentWithJsonEncodeFlags(): void
    {
        $this->assertSame('"123"', $this->marshalAsString('123', 'json'));
        $this->assertSame('123', $this->marshalAsString('123', 'json', ['json_encode_flags' => JSON_NUMERIC_CHECK]));
    }

    public function testCreateCacheFile(): void
    {
        $cacheFileCount = \count(glob($this->cacheDir.\DIRECTORY_SEPARATOR.'*'));
        $this->marshalAsString(1, 'json');

        $this->assertCount($cacheFileCount + 1, glob($this->cacheDir.\DIRECTORY_SEPARATOR.'*'));
    }

    public function testCreateCacheFileInCustomDirectory(): void
    {
        $cacheDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('symfony_marshaller_test_');

        if (file_exists($cacheDir)) {
            array_map('unlink', glob($cacheDir.\DIRECTORY_SEPARATOR.'*'));
            rmdir($cacheDir);
        }

        $this->marshalAsString(1, 'json', ['cache_dir' => $cacheDir]);

        $this->assertFileExists($cacheDir);
        $this->assertCount(1, glob($cacheDir.\DIRECTORY_SEPARATOR.'*'));

        array_map('unlink', glob($cacheDir.\DIRECTORY_SEPARATOR.'*'));
        rmdir($cacheDir);
    }

    public function testCreateCacheFileOnlyIfNotExists(): void
    {
        $cacheFilename = sprintf('%s%s%s.json.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5('int'));
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents($cacheFilename, '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED_FILE"); };');

        $this->assertSame('CACHED_FILE', $this->marshalAsString(1, 'json'));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function marshalAsString(mixed $data, string $format, array $context = []): string
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'wb');
        marshal($data, $resource, 'json', $context);

        rewind($resource);
        $string = stream_get_contents($resource);
        fclose($resource);

        return $string;
    }
}
