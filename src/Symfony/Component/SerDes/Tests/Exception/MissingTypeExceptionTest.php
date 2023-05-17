<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\MissingTypeException;

class MissingTypeExceptionTest extends TestCase
{
    public function testCreateForProperty()
    {
        $class = $this->createStub(\ReflectionClass::class);
        $class->method('getName')->willReturn('class');

        $property = $this->createStub(\ReflectionProperty::class);
        $property->method('getName')->willReturn('property');
        $property->method('getDeclaringClass')->willReturn($class);

        $this->assertSame(
            'Type of "class::$property" property has not been defined.',
            MissingTypeException::forProperty($property)->getMessage(),
        );
    }
}
