<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional;

use Symfony\Component\TypeInfo\Type;

class TypeInfoTest extends AbstractWebTestCase
{
    public function testComponent()
    {
        static::bootKernel(['test_case' => 'TypeInfo']);

        $this->assertEquals(Type::union(Type::int(), Type::null()), static::getContainer()->get('type_info.resolver')->resolve('int|null'));
    }
}
