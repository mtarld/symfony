<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Context\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Marshal\TypeContextBuilder;
use Symfony\Component\Marshaller\Context\Option\TypeOption;

final class TypeContextBuilderTest extends TestCase
{
    public function testAddTypeToContext(): void
    {
        $contextBuilder = new TypeContextBuilder();

        $typeOption = new TypeOption('array<int, string>');

        $expectedContext = ['type' => 'array<int, string>'];

        $this->assertSame($expectedContext, $contextBuilder->build(new Context($typeOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new TypeContextBuilder();

        $this->assertSame([], $contextBuilder->build(new Context(), []));
    }
}
