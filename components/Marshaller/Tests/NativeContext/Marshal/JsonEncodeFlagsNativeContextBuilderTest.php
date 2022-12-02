<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;
use Symfony\Component\Marshaller\NativeContext\Marshal\JsonEncodeFlagsNativeContextBuilder;

final class JsonEncodeFlagsNativeContextBuilderTest extends TestCase
{
    public function testAddTypeToNativeContext(): void
    {
        $contextBuilder = new JsonEncodeFlagsNativeContextBuilder();

        $jsonEncodeFlagsOption = new JsonEncodeFlagsOption(JSON_BIGINT_AS_STRING);

        $expectedNativeContext = ['json_encode_flags' => JSON_BIGINT_AS_STRING];

        $this->assertSame($expectedNativeContext, $contextBuilder->build(new Context($jsonEncodeFlagsOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new JsonEncodeFlagsNativeContextBuilder();

        $this->assertSame([], $contextBuilder->build(new Context(), []));
    }
}
