<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum|null'] = static function ($stream, $offset, $length) use($flags) {
        return \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum::tryFrom(\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $offset, $length, $flags));
    };
    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum|null']($stream, 0, null);
};
