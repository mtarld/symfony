<?php

/**
 * @param resource $resource
 * @return mixed
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["mixed"] = static function (mixed $resource, int $offset, int $length) use ($jsonDecodeFlags): mixed {
        return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $offset, $length, $jsonDecodeFlags);
    };
    return ($providers["mixed"])($resource, 0, -1);
};
