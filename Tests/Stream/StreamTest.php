<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Stream;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Stream\Stream;

final class StreamTest extends TestCase
{
    public function testCreateStream(): void
    {
        $stream = new class () extends Stream {
            public function __construct()
            {
                parent::__construct('php://memory', 'w+b');
            }
        };

        $streamMetadata = stream_get_meta_data($stream->stream());

        $this->assertSame('php://memory', $streamMetadata['uri']);
        $this->assertSame('w+b', $streamMetadata['mode']);
    }

    public function testToString(): void
    {
        $stream = new class () extends Stream {
            public function __construct()
            {
                parent::__construct('php://memory', 'w+b');
            }
        };

        fwrite($stream->stream(), 'content');

        $this->assertSame('content', (string) $stream);
    }
}
