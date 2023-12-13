<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\Template\Encode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Template\Encode\Template;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithOtherDummies;
use Symfony\Component\JsonEncoder\Tests\TypeResolverAwareTrait;
use Symfony\Component\TypeInfo\Type;

class TemplateTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider templatePathDataProvider
     */
    public function testTemplatePath(string $expectedFilename, Type $type, string $encodeTo)
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->cacheDir,
        );

        $this->assertSame(sprintf('%s/%s', $this->cacheDir, $expectedFilename), $template->getPath($type, $encodeTo));
    }

    /**
     * @return iterable<array{0: string, 1: Type, 2: bool}>
     */
    public static function templatePathDataProvider(): iterable
    {
        yield ['7617cc4b435dae7c97211c6082923b47.encode.json.string.php', Type::int(), Template::ENCODE_TO_STRING];
        yield ['6e77b03690271cbee671df141e635536.encode.json.stream.php', Type::int(nullable: true), Template::ENCODE_TO_STREAM];
        yield ['070660c7e72aa3e14a93c1039279afb6.encode.json.resource.php', Type::mixed(), Template::ENCODE_TO_RESOURCE];
    }

    /**
     * @dataProvider templateContentDataProvider
     */
    public function testTemplateContent(string $fixture, Type $type)
    {
        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $template = new Template(
            new DataModelBuilder($propertyMetadataLoader),
            $this->cacheDir,
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/encode/%s.string.php', \dirname(__DIR__, 2), $fixture),
            $template->generateContent($type),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/encode/%s.stream.php', \dirname(__DIR__, 2), $fixture),
            $template->generateStreamContent($type),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/templates/encode/%s.resource.php', \dirname(__DIR__, 2), $fixture),
            $template->generateResourceContent($type),
        );
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function templateContentDataProvider(): iterable
    {
        yield ['scalar', Type::int()];
        yield ['mixed', Type::mixed()];
        yield ['backed_enum', Type::enum(DummyBackedEnum::class, Type::string())];
        yield ['nullable_backed_enum', Type::enum(DummyBackedEnum::class, Type::string(), nullable: true)];

        yield ['list', Type::list()];
        yield ['object_list', Type::list(Type::object(DummyWithNameAttributes::class))];
        yield ['nullable_object_list', Type::list(Type::object(DummyWithNameAttributes::class), nullable: true)];

        yield ['iterable_list', Type::iterableList()];

        yield ['dict', Type::dict()];
        yield ['object_dict', Type::dict(Type::object(DummyWithNameAttributes::class))];
        yield ['nullable_object_dict', Type::dict(Type::object(DummyWithNameAttributes::class), nullable: true)];
        yield ['iterable_dict', Type::iterableDict()];

        yield ['object', Type::object(DummyWithNameAttributes::class)];
        yield ['nullable_object', Type::object(DummyWithNameAttributes::class, nullable: true)];
        yield ['object_in_object', Type::object(DummyWithOtherDummies::class)];
    }
}
