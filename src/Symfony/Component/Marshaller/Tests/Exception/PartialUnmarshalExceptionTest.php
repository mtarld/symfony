<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\PartialUnmarshalException;

class PartialUnmarshalExceptionTest extends TestCase
{
    public function testMessage()
    {
        $this->assertSame(
            'The "php://memory" resource has been partially unmarshalled.',
            (new PartialUnmarshalException(fopen('php://memory', 'r'), null, []))->getMessage(),
        );
    }
}
