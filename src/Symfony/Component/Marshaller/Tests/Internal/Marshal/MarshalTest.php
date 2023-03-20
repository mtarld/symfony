<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;

use function Symfony\Component\Marshaller\marshal;

class MarshalTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_marshaller', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testCreateCacheFile()
    {
        marshal(1, fopen('php://temp', 'w'), 'json', []);

        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }

    public function testCreateCacheFileInCustomDirectory()
    {
        $cacheDir = sprintf('%s/%s', sys_get_temp_dir(), uniqid('symfony_marshaller_'));

        if (file_exists($cacheDir)) {
            array_map('unlink', glob($cacheDir.'/*'));
            rmdir($cacheDir);
        }

        marshal(1, fopen('php://temp', 'w'), 'json', ['cache_dir' => $cacheDir]);

        $this->assertFileExists($cacheDir);
        $this->assertCount(1, glob($cacheDir.'/*'));

        array_map('unlink', glob($cacheDir.'/*'));
        rmdir($cacheDir);
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        $cacheFilename = sprintf('%s/%s.json.php', $this->cacheDir, md5('int'));
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

    public function testThrowOnUnknownFormat()
    {
        $this->expectException(UnsupportedFormatException::class);

        marshal(null, fopen('php://temp', 'w'), 'unknown', []);
    }
}
