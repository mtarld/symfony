<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['?Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\JsonEncoder\Decode\Splitter::splitDict($stream, $offset, $length);
        if (null === $boundaries) {
            return null;
        }
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            match ($k) {
                'id' => $properties['id'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                'name' => $properties['name'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    return $providers['?Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy']($stream, 0, null);
};
