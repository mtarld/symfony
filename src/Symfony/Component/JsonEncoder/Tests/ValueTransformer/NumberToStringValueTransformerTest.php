<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\ValueTransformer;

use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\Exception\InvalidArgumentException;
use Symfony\Component\JsonEncoder\ValueTransformer\NumberToStringValueTransformer;

class NumberToStringValueTransformerTest extends TestCase
{
    /**
     * @requires extension bcmath
     */
    public function testTransformBcMathNumber()
    {
        $this->assertSame('10', (new NumberToStringValueTransformer())->transform(new Number(10)));
    }

    /**
     * @requires extension gmp
     */
    public function testTransformGmp()
    {
        $this->assertSame('10', (new NumberToStringValueTransformer())->transform(new \GMP(10)));
    }

    public function testTransformThrowWhenInvalidNativeValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The native value must be either a "%s" or a "%s".', Number::class, \GMP::class));

        (new NumberToStringValueTransformer())->transform(true);
    }
}
