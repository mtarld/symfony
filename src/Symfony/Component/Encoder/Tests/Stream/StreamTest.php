<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\Stream;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Encoder\Stream\Stream;

class StreamTest extends TestCase
{
    public function testCreateStream()
    {
        $stream = new class() extends Stream {
            public function __construct()
            {
                parent::__construct('php://memory', 'w+b');
            }
        };

        $streamMetadata = stream_get_meta_data($stream->getResource());

        $this->assertSame('php://memory', $streamMetadata['uri']);
        $this->assertSame('w+b', $streamMetadata['mode']);
    }

    public function testToString()
    {
        $stream = new class() extends Stream {
            public function __construct()
            {
                parent::__construct('php://memory', 'w+b');
            }
        };

        fwrite($stream->getResource(), 'content');

        $this->assertSame('content', (string) $stream);
    }
}
