<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Output;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Output\StreamOutput;

final class StreamOutputTest extends TestCase
{
    public function testCreateStream(): void
    {
        $output = new class () extends StreamOutput {
            public function __construct()
            {
                parent::__construct('php://memory');
            }
        };

        $streamMetadata = stream_get_meta_data($output->stream());

        $this->assertSame('php://memory', $streamMetadata['uri']);
        $this->assertSame('w+b', $streamMetadata['mode']);
    }

    public function testToString(): void
    {
        $output = new class () extends StreamOutput {
            public function __construct()
            {
                parent::__construct('php://memory');
            }
        };

        fwrite($output->stream(), 'content');

        $this->assertSame('content', (string) $output);
    }
}
