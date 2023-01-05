<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Name;

final class DummyWithNameAttributes
{
    #[Name('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
