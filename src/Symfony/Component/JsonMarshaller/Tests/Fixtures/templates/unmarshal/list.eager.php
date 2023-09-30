<?php

/**
 * @param resource $resource
 * @return array<int, mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["array<int, mixed>"] = static function (mixed $data): mixed {
        return $data;
    };
    return ($providers["array<int, mixed>"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
