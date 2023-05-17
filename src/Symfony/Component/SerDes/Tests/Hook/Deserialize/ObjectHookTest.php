<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Hook\Deserialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Hook\Deserialize\ObjectHook;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;

class ObjectHookTest extends TestCase
{
    /**
     * @dataProvider addGenericParameterTypesDataProvider
     *
     * @param array<class-string, array<string, string>> $expectedGenericParameterTypes
     * @param list<string>                               $templates
     */
    public function testAddGenericParameterTypes(array $expectedGenericParameterTypes, string $type, array $templates)
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTemplateFromClass')->willReturn($templates);

        $hookResult = (new ObjectHook($typeExtractor))($type, []);

        $this->assertSame($expectedGenericParameterTypes, $hookResult['context']['_symfony']['generic_parameter_types'] ?? []);
    }

    /**
     * @return iterable<array{0: array<class-string, array<string, string>>, 1: string, 2: list<string>}>
     */
    public static function addGenericParameterTypesDataProvider(): iterable
    {
        yield [[], 'int', []];
        yield [[], 'Foo<int>', ['T']];
        yield [[ClassicDummy::class => ['T' => 'int']], ClassicDummy::class.'<int>', ['T']];
        yield [[ClassicDummy::class => ['Tk' => 'int', 'Tv' => 'string']], ClassicDummy::class.'<int, string>', ['Tk', 'Tv']];
    }

    public function testThrowOnWrongGenericTypeCount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Given 1 generic parameters in "%s<int>", but 2 templates are defined in "%1$s".', ClassicDummy::class));

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTemplateFromClass')->willReturn(['Tk', 'Tv']);

        (new ObjectHook($typeExtractor))(ClassicDummy::class.'<int>', []);
    }
}
