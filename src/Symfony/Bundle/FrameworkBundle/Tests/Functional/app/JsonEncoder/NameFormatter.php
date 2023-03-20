<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional\app\JsonEncoder;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class NameFormatter
{
    public function uppercase(string $data): string
    {
        return strtoupper($data);
    }

    public function lowercase(string $data): string
    {
        return strtolower($data);
    }
}
