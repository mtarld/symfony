<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\Encode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Encode\EncodeAs;
use Symfony\Component\JsonEncoder\Encode\EncoderGenerator;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithOtherDummies;
use Symfony\Component\JsonEncoder\Tests\TypeResolverAwareTrait;
use Symfony\Component\TypeInfo\Type;

class EncoderGeneratorTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_encoder_encoder', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider generatedEncoderDataProvider
     */
    public function testGeneratedEncoder(string $fixture, Type $type)
    {
        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $generator = new EncoderGenerator(new DataModelBuilder($propertyMetadataLoader), $this->cacheDir);

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/encoder/%s.string.php', \dirname(__DIR__), $fixture),
            file_get_contents($generator->generate($type, EncodeAs::STRING)),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/encoder/%s.stream.php', \dirname(__DIR__), $fixture),
            file_get_contents($generator->generate($type, EncodeAs::STREAM)),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/encoder/%s.resource.php', \dirname(__DIR__), $fixture),
            file_get_contents($generator->generate($type, EncodeAs::RESOURCE)),
        );
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function generatedEncoderDataProvider(): iterable
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
