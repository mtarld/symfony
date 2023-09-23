<?php

namespace Symfony\Component\Encoder\Tests\Fixtures\Model;

class DummyWithMethods
{
    public int $id = 1;

    public static function const(): string
    {
        return 'CONST';
    }

    public function nonStatic(int $value): string
    {
        return (string) (3 * $value);
    }
}
