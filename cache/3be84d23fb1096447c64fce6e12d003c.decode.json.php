<?php

/**
 * @param resource $resource
 * @return string
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["string"] = static function (mixed $data): mixed {
        return $data;
    };
    return ($providers["string"])("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
