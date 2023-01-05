<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Marshal\JsonEncodeFlagsContextBuilder;
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;

final class JsonEncodeFlagsContextBuilderTest extends TestCase
{
    public function testAddTypeToContext(): void
    {
        $contextBuilder = new JsonEncodeFlagsContextBuilder();

        $jsonEncodeFlagsOption = new JsonEncodeFlagsOption(\JSON_BIGINT_AS_STRING);

        $expectedContext = ['json_encode_flags' => \JSON_BIGINT_AS_STRING];

        $this->assertSame($expectedContext, $contextBuilder->build(new Context($jsonEncodeFlagsOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new JsonEncodeFlagsContextBuilder();

        $this->assertSame([], $contextBuilder->build(new Context(), []));
    }
}
