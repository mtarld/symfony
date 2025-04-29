<?php

namespace Symfony\Component\JsonStreamer\Tests\Fixtures\Model;

use Symfony\Component\JsonStreamer\Attribute\StreamedName;

class DummyWithConstants
{
    #[StreamedName('@type')]
    public const string TYPE = 'Collection';
}
