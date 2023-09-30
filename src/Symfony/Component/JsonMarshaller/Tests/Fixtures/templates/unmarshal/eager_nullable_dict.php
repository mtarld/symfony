<?php

/**
 * @param resource $resource
 * @return ?array<string, mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?array<string, mixed>"] = static function (?iterable $data) use ($config, $instantiator, $services, &$providers): ?array {
        if (null === $data) {
            return null;
        }
        $iterable = static function (iterable $data) use ($config, $instantiator, $services, &$providers): iterable {
            foreach ($data as $k => $v) {
                yield $k => $v;
            }
        };
        return \iterator_to_array(($iterable)($data));
    };
    return ($providers["?array<string, mixed>"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
