<?php

return static function (\Symfony\Component\Encoder\Stream\StreamReaderInterface $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['?Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum'] = static function ($stream, $offset, $length) use($flags) {
        return \Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum::tryFrom(\Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $offset, $length, $flags));
    };
    return $providers['?Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum']($stream, 0, null);
};
