<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Tests\ValueTransformer;

use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueObjectToScalarValueTransformer;

class ValueObjectToScalarValueTransformerTest extends TestCase
{
    public function testTransformDateTime()
    {
        $valueTransformer = new ValueObjectToScalarValueTransformer();

        $this->assertSame(
            '2023-07-26T00:00:00+00:00',
            $valueTransformer->transform(new \DateTimeImmutable('2023-07-26', new \DateTimeZone('UTC')), []),
        );

        $this->assertSame(
            '26/07/2023 00:00:00',
            $valueTransformer->transform((new \DateTimeImmutable('2023-07-26', new \DateTimeZone('UTC')))->setTime(0, 0), [ValueObjectToScalarValueTransformer::DATE_TIME_FORMAT_KEY => 'd/m/Y H:i:s']),
        );
    }

    /**
     * @requires extension bcmath
     */
    public function testTransformBcMathNumber()
    {
        $this->assertSame('10', (new ValueObjectToScalarValueTransformer())->transform(new Number(10)));
    }

    /**
     * @requires extension gmp
     */
    public function testTransformGmp()
    {
        $this->assertSame('10', (new ValueObjectToScalarValueTransformer())->transform(new \GMP(10)));
    }

    public function testNoOpWhenInvalidValueType()
    {
        $this->assertTrue(true, (new ValueObjectToScalarValueTransformer())->transform(true));
    }
}
