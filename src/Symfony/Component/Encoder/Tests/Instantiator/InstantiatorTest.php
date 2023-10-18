<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\Instantiator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Encoder\Exception\UnexpectedValueException;
use Symfony\Component\Encoder\Instantiator\Instantiator;
use Symfony\Component\Encoder\Tests\Fixtures\Model\ClassicDummy;

class InstantiatorTest extends TestCase
{
    public function testInstantiate()
    {
        $expected = new ClassicDummy();
        $expected->id = 100;
        $expected->name = 'dummy';

        $properties = [
            'id' => 100,
            'name' => 'dummy',
        ];

        $this->assertEquals($expected, (new Instantiator())->instantiate(ClassicDummy::class, $properties));
    }

    public function testThrowOnInvalidProperty()
    {
        $this->expectException(UnexpectedValueException::class);

        (new Instantiator())->instantiate(ClassicDummy::class, [
            'id' => ['an', 'array'],
        ]);
    }
}
