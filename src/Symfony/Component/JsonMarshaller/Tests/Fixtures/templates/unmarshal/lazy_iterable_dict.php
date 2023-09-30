<?php

/**
 * @param resource $resource
 * @return iterable<string, mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["iterable<string, mixed>"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers, $jsonDecodeFlags): iterable {
        $boundaries = "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Splitter"::splitDict($resource, $offset, $length);
        $iterable = static function (mixed $resource, iterable $boundaries) use ($config, $instantiator, &$providers, $jsonDecodeFlags): iterable {
            foreach ($boundaries as $k => $b) {
                yield $k => "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
            }
        };
        return ($iterable)($resource, $boundaries);
    };
    return ($providers["iterable<string, mixed>"])($resource, 0, -1);
};
