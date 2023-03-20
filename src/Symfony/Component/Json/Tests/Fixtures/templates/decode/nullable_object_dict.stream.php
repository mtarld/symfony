<?php

/**
 * @param resource $resource
 * @return ?array<string,Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy>
 */
return static function (mixed $resource, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $jsonDecodeFlags = $config["json_decode_flags"] ?? 0;
    $providers["?array<string,Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy>"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): ?array {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitDict($resource, $offset, $length);
        if (null === $boundaries) {
            return null;
        }
        $iterable = static function (mixed $resource, iterable $boundaries) use ($config, $instantiator, $services, &$providers, $jsonDecodeFlags): iterable {
            foreach ($boundaries as $k => $b) {
                yield $k => ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"])($resource, $b[0], $b[1]);
            }
        };
        return \iterator_to_array(($iterable)($resource, $boundaries));
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
    return ($providers["?array<string,Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy>"])($resource, 0, -1);
};
