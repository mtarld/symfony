<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;

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

    public function testCreateCacheFile(): void
    {
        $cacheFileCount = \count(glob($this->cacheDir.\DIRECTORY_SEPARATOR.'*'));

        marshal(1, fopen('php://temp', 'w'), 'json', []);

        $this->assertCount($cacheFileCount + 1, glob($this->cacheDir.\DIRECTORY_SEPARATOR.'*'));
    }

    public function testCreateCacheFileInCustomDirectory(): void
    {
        $cacheDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('symfony_marshaller_test_');

        if (file_exists($cacheDir)) {
            array_map('unlink', glob($cacheDir.\DIRECTORY_SEPARATOR.'*'));
            rmdir($cacheDir);
        }

        marshal(1, fopen('php://temp', 'w'), 'json', ['cache_dir' => $cacheDir]);

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

        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');
        marshal(1, $resource, 'json', []);

        rewind($resource);

        $marshalled = stream_get_contents($resource);

        $this->assertSame('CACHED_FILE', $marshalled);
    }

    public function testThrowOnUnknownFormat(): void
    {
        $this->expectException(UnsupportedFormatException::class);

        marshal(null, fopen('php://temp', 'w'), 'unknown', []);
    }
}
