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
use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
use Symfony\Component\JsonStreamer\ValueTransformer\ScalarToValueObjectValueTransformer;

class ScalarToValueObjectValueTransformerTest extends TestCase
{
    public function testTransformToDateTime()
    {
        $valueTransformer = new ScalarToValueObjectValueTransformer(\DateTimeInterface::class);

        $this->assertEquals(new \DateTimeImmutable('2023-07-26'), $valueTransformer->transform('2023-07-26'));
        $this->assertEquals(
            (new \DateTimeImmutable('2023-07-26'))->setTime(0, 0),
            $valueTransformer->transform('26/07/2023 00:00:00', [ScalarToValueObjectValueTransformer::DATE_TIME_FORMAT_KEY => 'd/m/Y H:i:s']),
        );
    }

    public function testTransformToDateTimeThrowWhenInvalidDateTimeString()
    {
        $valueTransformer = new ScalarToValueObjectValueTransformer(\DateTimeInterface::class);

        try {
            $valueTransformer->transform('0', []);
            $this->fail(\sprintf('A "%s" exception must have been thrown.', InvalidArgumentException::class));
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Parsing datetime string \"0\" resulted in 1 errors: \nat position 0: Unexpected character", $e->getMessage());
        }

        try {
            $valueTransformer->transform('0', [ScalarToValueObjectValueTransformer::DATE_TIME_FORMAT_KEY => 'Y-m-d']);
            $this->fail(\sprintf('A "%s" exception must have been thrown.', InvalidArgumentException::class));
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Parsing datetime string \"0\" using format \"Y-m-d\" resulted in 1 errors: \nat position 1: Not enough data available to satisfy format", $e->getMessage());
        }
    }

    /**
     * @requires extension bcmath
     */
    public function testTransformToBcMathNumber()
    {
        $this->assertEquals(new Number(10), (new ScalarToValueObjectValueTransformer(Number::class))->transform('10'));
    }

    /**
     * @requires extension gmp
     */
    public function testTransformToGmpNumber()
    {
        $this->assertEquals(new \GMP(10), (new ScalarToValueObjectValueTransformer(\GMP::class))->transform('10'));
    }

    public function testNoOpWhenInvalidValueType()
    {
        $this->assertTrue((new ScalarToValueObjectValueTransformer(\DateTimeInterface::class))->transform(true));
    }

    public function testTransformThrowWhenInvalidValueObjectClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unhandled "invalid" value object.');

        (new ScalarToValueObjectValueTransformer('invalid'))->transform(10);
    }

    /**
     * @requires extension bcmath
     */
    public function testTransformThrowWhenInvalidJsonValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Unable to create a "%s"; the stream value must a parsable string or an int.', Number::class));

        (new ScalarToValueObjectValueTransformer(Number::class))->transform('not valid');
    }
}
