<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional\app\JsonEncoder\Dto;

use BcMath\Number;
use Symfony\Component\JsonEncoder\Attribute\JsonEncodable;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
#[JsonEncodable]
class DummyWithNumbers
{
    public Number $bcMathNumber;
    public \GMP $gmpNumber;
}
