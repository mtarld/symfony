<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Generation\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\Generation\HookContextBuilder;
use Symfony\Component\Marshaller\Context\Generation\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\Generation\TypeFormatterContextBuilder;
use Symfony\Component\Marshaller\Context\Marshal\JsonEncodeFlagsContextBuilder;
use Symfony\Component\Marshaller\Context\Marshal\TypeContextBuilder;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\Stream\MemoryStream;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class MarshallerTest extends TestCase
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
     * @dataProvider marshalJsonGenerateDataProvider
     */
    public function testMarshalJsonGenerate(string $expectedSource, string $type, ?Context $context): void
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $marshalGenerationContextBuilders = [
            new HookContextBuilder(),
            new TypeFormatterContextBuilder(),
            new NameAttributeContextBuilder(),
            new FormatterAttributeContextBuilder(),
        ];

        $this->assertSame($expectedSource, (new Marshaller($typeExtractor, [], $marshalGenerationContextBuilders, [], $this->cacheDir))->generate($type, 'json', $context));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: Context}>
     */
    public function marshalJsonGenerateDataProvider(): iterable
    {
        yield [
            <<<PHP
            <?php

            /**
             * @param int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods::doubleAndCastToString(\$data, \$context), \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'int', new Context(new TypeFormatterOption(['int' => DummyWithMethods::doubleAndCastToString(...)])), ];

        yield [
            <<<PHP
            <?php

            /**
             * @param int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$foo, \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'int', new Context(new HookOption([
                'int' => static function (string $type, string $accessor, array $context): array {
                    return [
                        'type' => 'string',
                        'accessor' => '$foo',
                        'context' => $context,
                    ];
                },
            ])), ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"foo\":");
                \\fwrite(\$resource, \json_encode(\$bar, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"foo\":");
                \\fwrite(\$resource, \json_encode(\$bar, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP, ClassicDummy::class, new Context(new HookOption([
                'property' => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                    return [
                        'name' => 'foo',
                        'type' => 'string',
                        'accessor' => '$bar',
                        'context' => $context,
                    ];
                },
            ])), ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"@id\":");
                \\fwrite(\$resource, \json_encode(\$object_0->id, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"name\":");
                \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP, DummyWithNameAttributes::class, null, ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"id\":");
                \\fwrite(\$resource, \json_encode(Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes::doubleAndCastToString(\$object_0->id, \$context), \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"name\":");
                \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP, DummyWithFormatterAttributes::class, null, ];
    }

    public function testJsonMarshal(): void
    {
        $marshalContextBuilders = [
            new TypeContextBuilder(),
            new JsonEncodeFlagsContextBuilder(),
        ];

        $marshaller = new Marshaller($this->createStub(TypeExtractorInterface::class), $marshalContextBuilders, [], [], $this->cacheDir);

        $marshaller->marshal('1', 'json', $output = new MemoryStream());
        $this->assertSame('"1"', (string) $output);

        $marshaller->marshal('1', 'json', $output = new MemoryStream(), new Context(new JsonEncodeFlagsOption(\JSON_NUMERIC_CHECK)));
        $this->assertSame('1', (string) $output);

        $marshaller->marshal(['foo' => 'bar'], 'json', $output = new MemoryStream(), new Context(new TypeOption('array<int, string>')));
        $this->assertSame('["bar"]', (string) $output);
    }
}
