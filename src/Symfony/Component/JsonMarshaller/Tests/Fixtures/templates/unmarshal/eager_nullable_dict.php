<?php

/**
 * @param resource $resource
 * @return ?array<string, mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["?array<string, mixed>"] = static function (?iterable $data) use ($config, $instantiator, &$providers): ?array {
        if (null === $data) {
            return null;
        }
        $iterable = static function (iterable $data) use ($config, $instantiator, &$providers): iterable {
            foreach ($data as $k => $v) {
                yield $k => ($providers["mixed"])($v);
            }
        };
        return \iterator_to_array(($iterable)($data));
    };
    $providers["mixed"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        return $data;
    };
    return ($providers["?array<string, mixed>"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $config));
};
