<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<string,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy>'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitDict($stream, $offset, $length);
        $iterable = static function ($stream, $data) use($config, $instantiator, $services, &$providers, $flags) {
            foreach ($data as $k => $v) {
                (yield $k => $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy']($stream, $v[0], $v[1]));
            }
        };
        return \iterator_to_array($iterable($stream, $data));
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($data as $k => $v) {
            match ($k) {
                'id' => $properties['id'] = static function () use($stream, $v, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
                },
                'name' => $properties['name'] = static function () use($stream, $v, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    return $providers['array<string,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy>']($stream, 0, null);
};
