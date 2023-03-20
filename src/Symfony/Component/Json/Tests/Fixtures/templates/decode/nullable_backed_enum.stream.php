<?php

/**
 * @param resource $resource
 * @return ?Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"] = static function (mixed $resource, int $offset, int $length) use ($jsonDecodeFlags): mixed {
        return "Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"::tryFrom("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $offset, $length, $jsonDecodeFlags));
    };
    return ($providers["?Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"])($resource, 0, -1);
};
