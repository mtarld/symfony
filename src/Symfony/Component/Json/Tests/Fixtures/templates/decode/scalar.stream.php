<?php

/**
 * @param resource $resource
 * @return int
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["int"] = static function (mixed $resource, int $offset, int $length) use ($jsonDecodeFlags): mixed {
        return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $offset, $length, $jsonDecodeFlags);
    };
    return ($providers["int"])($resource, 0, -1);
};
