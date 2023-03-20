<?php

/**
 * @param resource $resource
 * @return Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"] = static function (mixed $data): mixed {
        return "Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"::from($data);
    };
    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum"])("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
