<?php

/**
 * @param resource $resource
 * @return Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers, $jsonDecodeFlags): Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy {
        $boundaries = "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Splitter"::splitDict($resource, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $b) {
            if ("id" === $k) {
                $properties["id"] = static function () use ($resource, $b, $config, $instantiator, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
            if ("name" === $k) {
                $properties["name"] = static function () use ($resource, $b, $config, $instantiator, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy", $properties);
    };
    return ($providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy"])($resource, 0, -1);
};
