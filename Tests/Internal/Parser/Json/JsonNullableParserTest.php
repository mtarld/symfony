<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Parser\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonNullableParser;

final class JsonNullableParserTest extends TestCase
{
    public function testParseNull(): void
    {
        $tokens = new \ArrayIterator(['null']);
        $handle = static function () {
            return 'NESTED';
        };

        $this->assertSame(null, (new JsonNullableParser())->parse($tokens, $handle, []));
    }

    public function testParseNotNull(): void
    {
        $tokens = new \ArrayIterator(['"foo"']);
        $callableArguments = null;

        $handle = static function () use (&$callableArguments) {
            $callableArguments = \func_get_args();

            return 'NESTED';
        };

        $value = (new JsonNullableParser())->parse($tokens, $handle, []);

        $this->assertSame([$tokens], $callableArguments);
        $this->assertSame('NESTED', $value);
    }

    public function testSelectNextTokenWhenNull(): void
    {
        $tokens = new \ArrayIterator(['null', 'NEXT_TOKEN']);
        $handle = static function () {
            return 'NESTED';
        };

        (new JsonNullableParser())->parse($tokens, $handle, []);

        $this->assertSame('NEXT_TOKEN', $tokens->current());
    }
}
