<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;

final class HookOptionTest extends TestCase
{
    public function testCannotCreateWithInvalidFormatter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Hook "hook" of attribute "%s" is an invalid callable.', HookOption::class));

        new HookOption(['hook' => true]);
    }
}
