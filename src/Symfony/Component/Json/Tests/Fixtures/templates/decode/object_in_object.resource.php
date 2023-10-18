<?php

/**
 * @param resource $stream
 * @return Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies
 */
return static function (mixed $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $flags = $config["json_decode_flags"] ?? 0;
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies"] = static function (mixed $stream, int $offset, ?int $length) use ($config, $instantiator, $services, &$providers, $flags): Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            if ("name" === $k) {
                $properties["name"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
            if ("otherDummyOne" === $k) {
                $properties["otherDummyOne"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes"])($stream, $boundary[0], $boundary[1]);
                };
                continue;
            }
            if ("otherDummyTwo" === $k) {
                $properties["otherDummyTwo"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"])($stream, $boundary[0], $boundary[1]);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies", $properties);
    };
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes"] = static function (mixed $stream, int $offset, ?int $length) use ($config, $instantiator, $services, &$providers, $flags): Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            if ("@id" === $k) {
                $properties["id"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
            if ("name" === $k) {
                $properties["name"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes", $properties);
    };
    $providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"] = static function (mixed $stream, int $offset, ?int $length) use ($config, $instantiator, $services, &$providers, $flags): Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            if ("id" === $k) {
                $properties["id"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
            if ("name" === $k) {
                $properties["name"] = static function () use ($stream, $boundary, $config, $instantiator, $services, &$providers, $flags): mixed {
                    return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy", $properties);
    };
    return ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies"])($stream, 0, null);
};
