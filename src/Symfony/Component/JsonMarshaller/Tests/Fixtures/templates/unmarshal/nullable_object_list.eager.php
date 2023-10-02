<?php

/**
 * @param resource $resource
 * @return ?array<int, Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?array<int, Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy>"] = static function (?iterable $data) use ($config, $instantiator, $services, &$providers): ?array {
        if (null === $data) {
            return null;
        }
        $iterable = static function (iterable $data) use ($config, $instantiator, $services, &$providers): iterable {
            foreach ($data as $k => $v) {
                yield $k => ($providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy"])($v);
            }
        };
        return \iterator_to_array(($iterable)($data));
    };
    $providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy"] = static function (?array $data) use ($config, $instantiator, $services, &$providers): Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy {
        return $instantiator->instantiate("Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy", \array_filter(["id" => $data["id"] ?? "_symfony_missing_value", "name" => $data["name"] ?? "_symfony_missing_value"], static function (mixed $v): bool {
            return "_symfony_missing_value" !== $v;
        }));
    };
    return ($providers["?array<int, Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy>"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
