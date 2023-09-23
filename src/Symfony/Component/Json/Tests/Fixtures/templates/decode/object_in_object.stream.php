<?php

/**
 * @param resource $resource
 * @return Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($resource, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $b) {
            if ("name" === $k) {
                $properties["name"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
            if ("otherDummyOne" === $k) {
                $properties["otherDummyOne"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes"])($resource, $b[0], $b[1]);
                };
                continue;
            }
            if ("otherDummyTwo" === $k) {
                $properties["otherDummyTwo"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"])($resource, $b[0], $b[1]);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies", $properties);
    };
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($resource, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $b) {
            if ("@id" === $k) {
                $properties["id"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
            if ("name" === $k) {
                $properties["name"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes", $properties);
    };
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($resource, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $b) {
            if ("id" === $k) {
                $properties["id"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
            if ("name" === $k) {
                $properties["name"] = static function () use ($resource, $b, $config, $instantiator, $services, &$providers, $jsonDecodeFlags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decode($resource, $b[0], $b[1], $jsonDecodeFlags);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy", $properties);
    };
    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies"])($resource, 0, -1);
};
