<?php

/**
 * @param resource $resource
 * @return Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithOtherDummies
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\DummyWithOtherDummies"] = static function (?array $data) use ($config, $instantiator, $services, &$providers): Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithOtherDummies {
        $properties = [];
        if (isset($data["name"])) {
            $properties["name"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return $data["name"];
            };
        }
        if (isset($data["otherDummyOne"])) {
            $properties["otherDummyOne"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return ($providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\DummyWithNameAttributes"])($data["otherDummyOne"]);
            };
        }
        if (isset($data["otherDummyTwo"])) {
            $properties["otherDummyTwo"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return ($providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\ClassicDummy"])($data["otherDummyTwo"]);
            };
        }
        return $instantiator->instantiate("Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\DummyWithOtherDummies", $properties);
    };
    $providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\DummyWithNameAttributes"] = static function (?array $data) use ($config, $instantiator, $services, &$providers): Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithNameAttributes {
        $properties = [];
        if (isset($data["@id"])) {
            $properties["id"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return $data["@id"];
            };
        }
        if (isset($data["name"])) {
            $properties["name"] = static function () use ($data, $config, $instantiator, $services, &$providers): mixed {
                return $data["name"];
            };
        }
        return $instantiator->instantiate("Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\DummyWithNameAttributes", $properties);
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
    return ($providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Dto\\DummyWithOtherDummies"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $jsonDecodeFlags));
};
