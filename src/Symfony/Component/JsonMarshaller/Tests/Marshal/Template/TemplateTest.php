<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Marshal\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithOtherDummies;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;

class TemplateTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_marshaller_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider templatePathDataProvider
     */
    public function testTemplatePath(string $expectedFilename, Type $type, bool $forStream)
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(new ReflectionTypeExtractor())),
            $this->cacheDir,
        );

        $this->assertSame(sprintf('%s/%s', $this->cacheDir, $expectedFilename), $template->path($type, $forStream));
    }

    /**
     * @return iterable<array{0: string, 1: Type, 2: bool}>
     */
    public static function templatePathDataProvider(): iterable
    {
        yield ['7617cc4b435dae7c97211c6082923b47.marshal.json.string.php', Type::int(), false];
        yield ['6e77b03690271cbee671df141e635536.marshal.json.string.php', Type::int(nullable: true), false];
        yield ['070660c7e72aa3e14a93c1039279afb6.marshal.json.stream.php', Type::mixed(), true];
        yield ['c486b01895febbc4130485acd2c56d11.marshal.json.string.php', Type::class(ClassicDummy::class), false];
        yield ['c486b01895febbc4130485acd2c56d11.marshal.json.stream.php', Type::class(ClassicDummy::class), true];
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
            new DataModelBuilder($propertyMetadataLoader),
            $this->cacheDir,
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/marshal/%s.stream.php', \dirname(__DIR__, 2), $fixture),
            $template->content($type, true),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/marshal/%s.string.php', \dirname(__DIR__, 2), $fixture),
            $template->content($type, false),
        );
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function templateContentDataProvider(): iterable
    {
        yield ['scalar', Type::int()];
        yield ['mixed', Type::mixed()];
        yield ['backed_enum', Type::enum(DummyBackedEnum::class)];
        yield ['nullable_backed_enum', Type::enum(DummyBackedEnum::class, nullable: true)];

        yield ['list', Type::list()];
        yield ['object_list', Type::list(Type::class(ClassicDummy::class))];
        yield ['nullable_object_list', Type::list(Type::class(ClassicDummy::class), nullable: true)];
        yield ['iterable_list', Type::iterableList()];

        yield ['dict', Type::dict()];
        yield ['object_dict', Type::dict(Type::class(ClassicDummy::class))];
        yield ['nullable_object_dict', Type::dict(Type::class(ClassicDummy::class), nullable: true)];
        yield ['iterable_dict', Type::iterableDict()];

        yield ['object', Type::class(ClassicDummy::class)];
        yield ['nullable_object', Type::class(ClassicDummy::class, nullable: true)];
        yield ['object_in_object', Type::class(DummyWithOtherDummies::class)];
    }
}
