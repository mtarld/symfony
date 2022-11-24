<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\PropertyTypeOption;

final class PropertyTypeOptionTest extends TestCase
{
    public function testCreate(): void
    {
        $option = new PropertyTypeOption([
            'classOne' => [
                'propertyOne' => 'typeOne',
                'propertyTwo' => 'typeTwo',
            ],
            'classTwo' => [
                'propertyOne' => 'typeThree',
            ],
        ]);

        $this->assertSame([
            'classOne::$propertyOne' => 'typeOne',
            'classOne::$propertyTwo' => 'typeTwo',
            'classTwo::$propertyOne' => 'typeThree',
        ], $option->types);
    }
}
