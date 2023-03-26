<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\UnsupportedFormatException;

use function Symfony\Component\SerDes\serialize;

class SerializeTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_ser_des', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testCreateCacheFile()
    {
        serialize(1, fopen('php://temp', 'w'), 'json', []);

        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }

    public function testCreateCacheFileInCustomDirectory()
    {
        $cacheDir = sprintf('%s/%s', sys_get_temp_dir(), uniqid('symfony_ser_des_'));

        if (file_exists($cacheDir)) {
            array_map('unlink', glob($cacheDir.'/*'));
            rmdir($cacheDir);
        }

        serialize(1, fopen('php://temp', 'w'), 'json', ['cache_dir' => $cacheDir]);

        $this->assertFileExists($cacheDir);
        $this->assertCount(1, glob($cacheDir.'/*'));

        array_map('unlink', glob($cacheDir.'/*'));
        rmdir($cacheDir);
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        $cacheFilename = sprintf('%s/%s.json.php', $this->cacheDir, hash('xxh128', 'int'));
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents($cacheFilename, '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED_FILE"); };');

        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');
        serialize(1, $resource, 'json', []);

        rewind($resource);

        $serialized = stream_get_contents($resource);

        $this->assertSame('CACHED_FILE', $serialized);
    }

    public function testThrowOnUnknownFormat()
    {
        $this->expectException(UnsupportedFormatException::class);

        serialize(null, fopen('php://temp', 'w'), 'unknown', []);
    }
}
