<?php

/**
 * @param resource $resource
 * @return iterable<int, mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["iterable<int, mixed>"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): iterable {
        $boundaries = "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Splitter"::splitList($resource, $offset, $length);
        $iterable = static function (mixed $resource, iterable $boundaries) use ($config, $instantiator, &$providers): iterable {
            foreach ($boundaries as $k => $b) {
                yield $k => ($providers["mixed"])($resource, $b[0], $b[1]);
            }
        };
        return ($iterable)($resource, $boundaries);
    };
    $providers["mixed"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, $offset, $length, $config);
        return $data;
    };
    return ($providers["iterable<int, mixed>"])($resource, 0, -1);
};
