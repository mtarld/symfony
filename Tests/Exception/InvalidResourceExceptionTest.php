<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidResourceException;

final class InvalidResourceExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $this->assertSame(
            'Resource "php://memory" is not valid.',
            (new InvalidResourceException(fopen('php://memory', 'r')))->getMessage(),
        );
    }
}
