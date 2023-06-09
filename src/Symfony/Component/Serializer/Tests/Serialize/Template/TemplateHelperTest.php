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
use Symfony\Component\Serializer\Serialize\Template\TemplateHelper;
use Symfony\Component\Serializer\Serialize\Template\TemplateVariation;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;

class TemplateHelperTest extends TestCase
{
    /**
     * @dataProvider templateFilenameDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testTemplateFilename(string $expectedType, ?string $expectedVariant, string $expectedFormat, string $type, string $format, array $context)
    {
        $hash = hash('xxh128', $expectedType);
        if (null !== $expectedVariant) {
            $hash .= '.'.hash('xxh128', $expectedVariant);
        }

        $this->assertSame(sprintf('%s.%s.php', $hash, $expectedFormat), (new TemplateHelper())->templateFilename($type, $format, $context));
    }

    /**
     * @return iterable<array{0: string, 1: ?string, 2: string, 3: string, 4: string, 5: array<string, mixed>}>
     */
    public static function templateFilenameDataProvider(): iterable
    {
        yield ['int', null, 'json', 'int', 'json', []];
        yield ['int', null, 'xml', 'int', 'xml', []];
        yield [ClassicDummy::class, null, 'json', ClassicDummy::class, 'json', []];
        yield [ClassicDummy::class, 'group-a', 'json', ClassicDummy::class, 'json', ['groups' => ['a']]];
        yield [ClassicDummy::class, 'group-a_group-b', 'json', ClassicDummy::class, 'json', ['groups' => ['a', 'b']]];
        yield [ClassicDummy::class, 'group-a_group-b', 'json', ClassicDummy::class, 'json', ['groups' => ['b', 'a']]];
    }

    public function testClassTemplateVariants()
    {
        $templateHelper = new TemplateHelper();

        $this->assertSame([[]], $templateHelper->classTemplateVariants(ClassicDummy::class));
        $this->assertEquals([
            [],
            [TemplateVariation::createGroup('one')],
            [TemplateVariation::createGroup('two')],
            [TemplateVariation::createGroup('two'), TemplateVariation::createGroup('one')],
            [TemplateVariation::createGroup('three')],
            [TemplateVariation::createGroup('three'), TemplateVariation::createGroup('one')],
            [TemplateVariation::createGroup('three'), TemplateVariation::createGroup('two')],
            [TemplateVariation::createGroup('three'), TemplateVariation::createGroup('two'), TemplateVariation::createGroup('one')],
        ], $templateHelper->classTemplateVariants(DummyWithGroups::class));
    }
}
