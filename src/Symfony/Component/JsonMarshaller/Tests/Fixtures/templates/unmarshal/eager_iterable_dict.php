<?php

/**
 * @param resource $resource
 * @return iterable<string, mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["iterable<string, mixed>"] = static function (?iterable $data) use ($config, $instantiator, $services, &$providers): iterable {
        $iterable = static function (iterable $data) use ($config, $instantiator, $services, &$providers): iterable {
            foreach ($data as $k => $v) {
                yield $k => $v;
            }
        };
        return ($iterable)($data);
    };
    return ($providers["iterable<string, mixed>"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
