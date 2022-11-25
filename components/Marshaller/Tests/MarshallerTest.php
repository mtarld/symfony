<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\NativeContext\CacheDirNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeExtractorNativeContextBuilder;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

final class MarhsallerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().'/symfony_marshaller';
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
     * @dataProvider generateTemplateDataProvider
     *
     * @param list<string> $expectedLines
     */
    public function testGenerateTemplate(array $expectedLines, string $type, ?Context $context): void
    {
        $cacheDirNativeContextBuilder = new CacheDirNativeContextBuilder($this->cacheDir);

        $marshalContextBuilders = [
            $cacheDirNativeContextBuilder,
        ];

        $generateContextBuilders = [
            $cacheDirNativeContextBuilder,
            new TypeExtractorNativeContextBuilder(new PhpstanTypeExtractor(new ReflectionTypeExtractor())),
        ];

        $lines = explode("\n", (new Marshaller($marshalContextBuilders, $generateContextBuilders, $this->cacheDir))->generate($type, 'json', $context));
        array_pop($lines);

        $this->assertSame($expectedLines, $lines);
    }

    /**
     * @return iterable<array{0: list<string>, 1: string, 2: Context}
     */
    public function generateTemplateDataProvider(): iterable
    {
        $typeExtractorNativeContextBuilder = new TypeExtractorNativeContextBuilder(new PhpstanTypeExtractor(new ReflectionTypeExtractor()));

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param int $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, $data);',
            '};',
        ], 'int', null];
    }
}
