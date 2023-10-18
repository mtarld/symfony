<?php

/**
 * @return mixed
 */
return static function (string $string, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $flags = $config["json_decode_flags"] ?? 0;
    $providers["mixed"] = static function (mixed $data): mixed {
        return $data;
    };
    return ($providers["mixed"])("\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeString($string, $flags));
};
