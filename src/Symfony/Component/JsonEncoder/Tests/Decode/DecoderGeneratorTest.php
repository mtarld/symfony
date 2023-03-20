<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\Decode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Decode\DecodeFrom;
use Symfony\Component\JsonEncoder\Decode\DecoderGenerator;
use Symfony\Component\JsonEncoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithOtherDummies;
use Symfony\Component\JsonEncoder\Tests\TypeResolverAwareTrait;
use Symfony\Component\TypeInfo\Type;

class DecoderGeneratorTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_encoder_decoder', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider generatedDecoderDataProvider
     */
    public function testGeneratedDecoder(string $fixture, Type $type)
    {
        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $generator = new DecoderGenerator(new DataModelBuilder($propertyMetadataLoader), $this->cacheDir);

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/decoder/%s.string.php', \dirname(__DIR__), $fixture),
            file_get_contents($generator->generate($type, DecodeFrom::STRING)),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/decoder/%s.stream.php', \dirname(__DIR__), $fixture),
            file_get_contents($generator->generate($type, DecodeFrom::STREAM)),
        );

        $this->assertStringEqualsFile(
            sprintf('%s/Fixtures/decoder/%s.resource.php', \dirname(__DIR__), $fixture),
            file_get_contents($generator->generate($type, DecodeFrom::RESOURCE)),
        );
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function generatedDecoderDataProvider(): iterable
    {
        yield ['scalar', Type::int()];
        yield ['mixed', Type::mixed()];
        yield ['backed_enum', Type::enum(DummyBackedEnum::class, Type::string())];
        yield ['nullable_backed_enum', Type::enum(DummyBackedEnum::class, Type::string(), nullable: true)];

        yield ['list', Type::list()];
        yield ['object_list', Type::list(Type::object(ClassicDummy::class))];
        yield ['nullable_object_list', Type::list(Type::object(ClassicDummy::class), nullable: true)];
        yield ['iterable_list', Type::iterableList()];

        yield ['dict', Type::dict()];
        yield ['object_dict', Type::dict(Type::object(ClassicDummy::class))];
        yield ['nullable_object_dict', Type::dict(Type::object(ClassicDummy::class), nullable: true)];
        yield ['iterable_dict', Type::iterableDict()];

        yield ['object', Type::object(ClassicDummy::class)];
        yield ['nullable_object', Type::object(ClassicDummy::class, nullable: true)];
        yield ['object_in_object', Type::object(DummyWithOtherDummies::class)];
    }
}
