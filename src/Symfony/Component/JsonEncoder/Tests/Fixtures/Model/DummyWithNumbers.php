<?php

namespace Symfony\Component\JsonEncoder\Tests\Fixtures\Model;

use BcMath\Number;

class DummyWithNumbers
{
    public \GMP $gmpNumber;
    public Number $bcMathNumber;
}
