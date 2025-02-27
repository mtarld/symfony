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
use Symfony\Component\JsonEncoder\ValueTransformer\ScalarToNumberValueTransformer;

class ScalarToNumberValueTransformerTest extends TestCase
{
    /**
     * @requires extension bcmath
     */
    public function testTransformToBcMathNumber()
    {
        $this->assertEquals(new Number(10), (new ScalarToNumberValueTransformer(Number::class))->transform('10'));
    }

    /**
     * @requires extension gmp
     */
    public function testTransformToGmpNumber()
    {
        $this->assertEquals(new \GMP(10), (new ScalarToNumberValueTransformer(\GMP::class))->transform('10'));
    }

    public function testTransformThrowWhenInvalidNumberClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('Only "%s" and "%s" types are supported by "%s::transform()".', Number::class, \GMP::class, ScalarToNumberValueTransformer::class));

        (new ScalarToNumberValueTransformer('invalid'))->transform(10);
    }

    public function testTransformThrowWhenInvalidJsonValueType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The stream value is neither a string nor an int; you should pass a parsable string or an int.');

        (new ScalarToNumberValueTransformer(\GMP::class))->transform(true);
    }

    /**
     * @requires extension bcmath
     */
    public function testTransformThrowWhenInvalidJsonValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Unable to create a "%s"; the stream value must a parsable string or an int.', Number::class));

        (new ScalarToNumberValueTransformer(Number::class))->transform('not valid');
    }
}
