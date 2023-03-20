<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Serialize\Template;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilder;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilderInterface;
use Symfony\Component\Serializer\Serialize\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Template\JsonTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\NormalizerEncoderTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\Template;
use Symfony\Component\Serializer\Template\GroupTemplateVariation;
use Symfony\Component\Serializer\Template\TemplateVariation;
use Symfony\Component\Serializer\Template\TemplateVariationExtractorInterface;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;

class TemplateTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_serializer_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider templatePathDataProvider
     *
     * @param list<TemplateVariation> $variations
     */
    public function testTemplatePath(string $expectedFilename, Type $type, array $variations, bool $lazy)
    {
        $templateVariationExtractor = $this->createStub(TemplateVariationExtractorInterface::class);
        $templateVariationExtractor->method('extractVariationsFromConfig')->willReturn($variations);

        $template = new Template(
            $templateVariationExtractor,
            $this->createStub(DataModelBuilderInterface::class),
            [],
            $this->cacheDir,
            false,
        );

        $this->assertSame(sprintf('%s/%s', $this->cacheDir, $expectedFilename), $template->path($type, 'format', new SerializeConfig()));
    }

    /**
     * @return iterable<array{0: string, 1: Type, 2: list<TemplateVariation>, 3: bool}>
     */
    public static function templatePathDataProvider(): iterable
    {
        yield ['7617cc4b435dae7c97211c6082923b47.serialize.format.php', Type::int(), [], false];
        yield ['6e77b03690271cbee671df141e635536.serialize.format.php', Type::int(nullable: true), [], false];
        yield ['070660c7e72aa3e14a93c1039279afb6.serialize.format.php', Type::mixed(), [], true];
        yield ['c13f5526678495e20da82e0a7c1c300b.serialize.format.php', Type::class(ClassicDummy::class), [], false];
        yield [
            'c13f5526678495e20da82e0a7c1c300b.aa043a938b34c9e6dbe35f74e6b11dd2.serialize.format.php',
            Type::class(ClassicDummy::class),
            [new GroupTemplateVariation('foo')],
            true,
        ];
        yield [
            'c13f5526678495e20da82e0a7c1c300b.357ebc0d58122a5e2949ecd9dc04c02b.serialize.format.php',
            Type::class(ClassicDummy::class),
            [new GroupTemplateVariation('foo'), new GroupTemplateVariation('bar')],
            true,
        ];
    }

    /**
     * @dataProvider templateContentDataProvider
     */
    public function testTemplateContent(string $fixture, Type $type)
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $propertyMetadataLoader = new TypePropertyMetadataLoader(
            new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor),
            $typeExtractor,
        );

        $template = new Template(
            $this->createStub(TemplateVariationExtractorInterface::class),
            new DataModelBuilder($propertyMetadataLoader, $this->createStub(ContainerInterface::class)),
            [
                'normalizer' => new NormalizerEncoderTemplateGenerator('ENCODER'),
                'json' => new JsonTemplateGenerator(),
            ],
            $this->cacheDir,
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/serialize/normalizer_%s.php', \dirname(__DIR__, 2), $fixture),
            $template->content($type, 'normalizer', new SerializeConfig()),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/serialize/json_%s.php', \dirname(__DIR__, 2), $fixture),
            $template->content($type, 'json', new SerializeConfig()),
        );
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function templateContentDataProvider(): iterable
    {
        yield ['scalar', Type::int()];
        yield ['nullable_scalar', Type::string(nullable: true)];
        yield ['mixed', Type::mixed()];
        yield ['backed_enum', Type::enum(DummyBackedEnum::class)];

        yield ['list', Type::list()];
        yield ['nullable_list', Type::list(nullable: true)];
        yield ['iterable_list', Type::iterableList()];
        yield ['dict', Type::dict()];
        yield ['nullable_dict', Type::dict(nullable: true)];
        yield ['iterable_dict', Type::iterableDict()];

        yield ['object', Type::class(ClassicDummy::class)];
        yield ['nullable_object', Type::class(ClassicDummy::class, nullable: true)];
    }

    public function testThrowOnUnsupportedFormat()
    {
        $this->expectException(UnsupportedFormatException::class);

        $template = new Template(
            $this->createStub(TemplateVariationExtractorInterface::class),
            $this->createStub(DataModelBuilderInterface::class),
            [],
            $this->cacheDir,
            false,
        );
        $template->content(Type::int(), 'format', new SerializeConfig());
    }
}
