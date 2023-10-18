<?php

/**
 * @param resource $stream
 * @return ?array<int,Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy>
 */
return static function (mixed $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $flags = $config["json_decode_flags"] ?? 0;
    $providers["?array<int,Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy>"] = static function (mixed $stream, int $offset, ?int $length) use ($config, $instantiator, $services, &$providers, $flags): ?array {
        $boundaries = "\\Symfony\\Component\\Json\\Template\\Decode\\Splitter"::splitList($stream, $offset, $length);
        if (null === $boundaries) {
            return null;
        }
        $iterable = static function (mixed $stream, iterable $boundaries) use ($config, $instantiator, $services, &$providers, $flags): iterable {
            foreach ($boundaries as $k => $boundary) {
                yield $k => ($providers["Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy"])($stream, $boundary[0], $boundary[1]);
            }
        };
        return \iterator_to_array(($iterable)($stream, $boundaries));
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
    return ($providers["?array<int,Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy>"])($stream, 0, null);
};
