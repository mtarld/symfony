<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

final class DummyWithConstructorWithRequiredValues
{
    public int $id = 1;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
