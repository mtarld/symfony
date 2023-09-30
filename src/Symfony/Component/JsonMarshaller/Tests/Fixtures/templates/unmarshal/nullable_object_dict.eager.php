<?php

/**
 * @param resource $resource
 * @return ?array<string, Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy>
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?array<string, Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy>"] = static function (?iterable $data) use ($config, $instantiator, $services, &$providers): ?array {
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
        $properties = [];
        if (isset($data["id"])) {
            $properties["id"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return $data["id"];
            };
        }
        if (isset($data["name"])) {
            $properties["name"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return $data["name"];
            };
        }
        return $instantiator->instantiate("Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy", $properties);
    };
    return ($providers["?array<string, Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy>"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
