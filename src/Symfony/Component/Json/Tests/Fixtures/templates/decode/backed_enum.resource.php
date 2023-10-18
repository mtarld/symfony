<?php

/**
 * @param resource $stream
 * @return Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum
 */
return static function (mixed $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $flags = $config["json_decode_flags"] ?? 0;
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"] = static function (mixed $stream, int $offset, ?int $length) use ($flags): mixed {
        return "Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"::from("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $offset, $length, $flags));
    };
    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"])($stream, 0, null);
};
