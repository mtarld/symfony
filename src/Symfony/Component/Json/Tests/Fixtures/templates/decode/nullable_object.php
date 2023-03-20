<?php

/**
 * @param resource $resource
 * @return ?Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"] = static function (?array $data) use ($config, $instantiator, $services, &$providers): ?Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy {
        if (null === $data) {
            return null;
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy", \array_filter(["id" => $data["id"] ?? "_symfony_missing_value", "name" => $data["name"] ?? "_symfony_missing_value"], static function (mixed $v): bool {
            return "_symfony_missing_value" !== $v;
        }));
    };
    return ($providers["?Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"])("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
