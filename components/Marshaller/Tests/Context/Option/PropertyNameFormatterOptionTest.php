<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\PropertyNameFormatterOption;

final class PropertyNameFormatterOptionTest extends TestCase
{
    public function testCreate(): void
    {
        $option = new PropertyNameFormatterOption([
            'classOne' => [
                'propertyOne' => $formatterOne = static function () {
                },
                'propertyTwo' => $formatterTwo = static function () {
                },
            ],
            'classTwo' => [
                'propertyOne' => $formatterThree = static function () {
                },
            ],
        ]);

        $this->assertSame([
            'classOne::$propertyOne' => $formatterOne,
            'classOne::$propertyTwo' => $formatterTwo,
            'classTwo::$propertyOne' => $formatterThree,
        ], $option->formatters);
    }

    public function testCannotCreateWithInvalidFormatter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Formatter "class::$property" of attribute "%s" is an invalid callable.', PropertyNameFormatterOption::class));

        new PropertyNameFormatterOption([
            'class' => [
                'property' => true,
            ],
        ]);
    }
}
