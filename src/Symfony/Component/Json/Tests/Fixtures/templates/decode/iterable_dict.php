<?php

/**
 * @param resource $resource
 * @return iterable<string,mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["iterable<string,mixed>"] = static function (mixed $data): mixed {
        return $data;
    };
    return ($providers["iterable<string,mixed>"])("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
