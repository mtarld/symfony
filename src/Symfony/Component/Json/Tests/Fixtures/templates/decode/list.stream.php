<?php

/**
 * @param resource $resource
 * @return array<int,mixed>
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["array<int,mixed>"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): array {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitList($resource, $offset, $length);
        $iterable = static function (mixed $resource, iterable $boundaries) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): iterable {
            foreach ($boundaries as $k => $b) {
                yield $k => "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
            }
        };
        return \iterator_to_array(($iterable)($resource, $boundaries));
    };
    return ($providers["array<int,mixed>"])($resource, 0, -1);
};
