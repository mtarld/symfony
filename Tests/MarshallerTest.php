<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\Generation as GenerationContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\Marshal as MarshalContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal as UnmarshalContextBuilder;
use Symfony\Component\Marshaller\Context\Option\CollectErrorsOption;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Context\Option\UnionSelectorOption;
use Symfony\Component\Marshaller\Exception\PartialUnmarshalException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\MarshallableResolver;
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
     * @dataProvider marshalGenerateDataProvider
     */
    public function testMarshalGenerate(string $expectedSource, string $type, ?Context $context): void
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $marshalGenerationContextBuilders = [
            new GenerationContextBuilder\HookContextBuilder(),
            new GenerationContextBuilder\TypeFormatterContextBuilder(),
            new GenerationContextBuilder\CachedNameAttributeContextBuilder(
                new GenerationContextBuilder\NameAttributeContextBuilder(new MarshallableResolver([__DIR__.'/Fixtures'])),
                new ArrayAdapter(),
            ),
            new GenerationContextBuilder\CachedFormatterAttributeContextBuilder(
                new GenerationContextBuilder\FormatterAttributeContextBuilder(new MarshallableResolver([__DIR__.'/Fixtures'])),
                new ArrayAdapter(),
            ),
        ];

        $this->assertSame($expectedSource, (new Marshaller($typeExtractor, [], $marshalGenerationContextBuilders, [], $this->cacheDir))->generate($type, 'json', $context));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: Context}>
     */
    public function marshalGenerateDataProvider(): iterable
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

            PHP,
            'int',
            new Context(new TypeFormatterOption(['int' => DummyWithMethods::doubleAndCastToString(...)])),
        ];

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

            PHP,
            'int',
            new Context(new HookOption([
                'int' => static function (string $type, string $accessor, array $context): array {
                    return [
                        'type' => 'string',
                        'accessor' => '$foo',
                        'context' => $context,
                    ];
                },
            ])),
        ];

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

            PHP,
            ClassicDummy::class,
            new Context(new HookOption([
                'property' => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                    return [
                        'name' => 'foo',
                        'type' => 'string',
                        'accessor' => '$bar',
                        'context' => $context,
                    ];
                },
            ])),
        ];

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

            PHP,
            DummyWithNameAttributes::class,
            null,
        ];

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

            PHP,
            DummyWithFormatterAttributes::class,
            null,
        ];
    }

    /**
     * @dataProvider marshalDataProvider
     */
    public function testMarshal(string $expectedMarshalled, mixed $data, ?Context $context): void
    {
        $marshalContextBuilders = [
            new MarshalContextBuilder\JsonEncodeFlagsContextBuilder(),
        ];

        $marshaller = new Marshaller($this->createStub(TypeExtractorInterface::class), $marshalContextBuilders, [], [], $this->cacheDir);

        $marshaller->marshal($data, 'json', $output = new MemoryStream(), $context);
        $this->assertSame($expectedMarshalled, (string) $output);
    }

    /**
     * @return iterable<array{0: string, 1: mixed, 2: ?Context}>
     */
    public function marshalDataProvider(): iterable
    {
        yield ['"1"', '1', null];
        yield ['1', '1', new Context(new JsonEncodeFlagsOption(\JSON_NUMERIC_CHECK))];
        yield ['["bar"]', ['foo' => 'bar'], new Context(new TypeOption('array<int, string>'))];
    }

    /**
     * @dataProvider unmarshalDataProvider
     */
    public function testUnmarshal(mixed $expectedUnmarshalled, string $content, string $type, ?Context $context): void
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $unmarshalContextBuilders = [
            new UnmarshalContextBuilder\HookContextBuilder(),
            new UnmarshalContextBuilder\CollectErrorsContextBuilder(),
            new UnmarshalContextBuilder\UnionSelectorContextBuilder(),
            new UnmarshalContextBuilder\CachedNameAttributeContextBuilder(
                new UnmarshalContextBuilder\NameAttributeContextBuilder(new MarshallableResolver([__DIR__.'/Fixtures'])),
                new ArrayAdapter(),
            ),
            new UnmarshalContextBuilder\CachedFormatterAttributeContextBuilder(
                new UnmarshalContextBuilder\FormatterAttributeContextBuilder(new MarshallableResolver([__DIR__.'/Fixtures'])),
                new ArrayAdapter(),
            ),
        ];

        $marshaller = new Marshaller($typeExtractor, [], [], $unmarshalContextBuilders, $this->cacheDir);

        $input = new MemoryStream();
        fwrite($input->resource(), $content);
        rewind($input->resource());

        $result = $marshaller->unmarshal($input, $type, 'json', $context);

        $this->assertEquals($expectedUnmarshalled, $result);
    }

    /**
     * @return iterable<array{0: mixed, 1: string, 2: string, 3: ?Context}>
     */
    public function unmarshalDataProvider(): iterable
    {
        yield [1, '1', 'int', null];
        yield [1, '"1"', 'int|string', new Context(new UnionSelectorOption(['int|string' => 'int']))];

        $dummy = new ClassicDummy();
        $dummy->name = 'HOOK_RESULT';

        yield [$dummy, '{"name": "the name"}', ClassicDummy::class, new Context(new HookOption([
            'property' => static function (\ReflectionClass $class, object $object, string $key, callable $value, array $context): void {
                if ('name' === $key) {
                    $object->{$key} = 'HOOK_RESULT';
                }
            },
        ]))];

        $dummy = new DummyWithNameAttributes();
        $dummy->id = 123;

        yield [$dummy, '{"@id": 123}', DummyWithNameAttributes::class, null];

        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;

        yield [$dummy, '{"id": 20}', DummyWithFormatterAttributes::class, null];
    }

    public function testPartiallyUnmarshal(): void
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $unmarshalContextBuilders = [
            new UnmarshalContextBuilder\HookContextBuilder(),
            new UnmarshalContextBuilder\CollectErrorsContextBuilder(),
        ];

        $marshaller = new Marshaller($typeExtractor, [], [], $unmarshalContextBuilders, $this->cacheDir);

        $input = new MemoryStream();
        fwrite($input->resource(), '[{"name": "ok"}, {"name": "ko"}, {"name": "ok"}, {"name": "ko"}]');
        rewind($input->resource());

        try {
            $marshaller->unmarshal($input, sprintf('array<int, %s>', ClassicDummy::class), 'json', new Context(new HookOption([
                sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $reflection, object $object, string $key, callable $value, array $context): void {
                    $name = $value('string', $context);
                    $object->name = 'ok' === $name ? 'ok' : new \stdClass();
                },
            ]), new CollectErrorsOption()));

            $this->fail(sprintf('"%s" has not been thrown.', PartialUnmarshalException::class));
        } catch (PartialUnmarshalException $e) {
            $okDummy = new ClassicDummy();
            $okDummy->name = 'ok';

            $koDummy = new ClassicDummy();

            $this->assertEquals([$okDummy, $koDummy, $okDummy, $koDummy], $e->unmarshalled);

            $this->assertCount(2, $e->errors);
            $this->assertContainsOnlyInstancesOf(UnexpectedTypeException::class, $e->errors);
        }
    }
}
