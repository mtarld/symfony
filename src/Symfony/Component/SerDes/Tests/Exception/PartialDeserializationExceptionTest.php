<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\PartialDeserializationException;

class PartialDeserializationExceptionTest extends TestCase
{
    public function testMessage()
    {
        $this->assertSame(
            'The "php://memory" resource has been partially deserialized.',
            (new PartialDeserializationException(fopen('php://memory', 'r'), null, []))->getMessage(),
        );
    }
}
