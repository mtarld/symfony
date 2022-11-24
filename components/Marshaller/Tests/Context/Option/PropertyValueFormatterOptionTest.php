<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\PropertyValueFormatterOption;

final class PropertyValueFormatterOptionTest extends TestCase
{
    public function testCreate(): void
    {
        $option = new PropertyValueFormatterOption([
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
        $this->expectExceptionMessage(sprintf('Formatter "class::$property" of attribute "%s" is an invalid callable.', PropertyValueFormatterOption::class));

        new PropertyValueFormatterOption([
            'class' => [
                'property' => true,
            ],
        ]);
    }
}
