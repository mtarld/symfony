<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Decoder;

use Symfony\Component\Marshaller\Input\InputInterface;

final class JsonDecoder implements DecoderInterface
{
    private JsonTokens $tokens;

    public function __construct(
        InputInterface $input,
    ) {
        $this->tokens = new JsonTokens($input);
    }

    public function decodeInt(mixed $data): int
    {
        return (int) $data;
    }

    public function decodeString(mixed $data): string
    {
        return (string) $data;
    }

    public function decodeDict(mixed $data, \Closure $unmarshal): iterable
    {
        return [];
    }

    public function decodeList(\Generator $data, \Closure $unmarshal): iterable
    {
        return [];
    }

    public function getIterator(): \Generator
    {
        yield from $this->tokens;
    }
}
