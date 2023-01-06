<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

final class DummyWithPrivateConstructor
{
    public int $id = 1;

    private function __construct()
    {
        $this->id = 2;
    }
}
