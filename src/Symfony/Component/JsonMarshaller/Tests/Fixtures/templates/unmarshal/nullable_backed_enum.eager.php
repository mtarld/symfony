<?php

/**
 * @param resource $resource
 * @return ?Symfony\Component\JsonMarshaller\Tests\Fixtures\Enum\DummyBackedEnum
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum"] = static function (mixed $data): mixed {
        if (null === $data) {
            return null;
        }
        return "Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum"::from($data);
    };
    return ($providers["?Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
