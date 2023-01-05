<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;

final class TypeFormatterOptionTest extends TestCase
{
    public function testCannotCreateWithInvalidFormatter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Formatter "type" of attribute "%s" is an invalid callable.', TypeFormatterOption::class));

        new TypeFormatterOption(['type' => true]);
    }
}
