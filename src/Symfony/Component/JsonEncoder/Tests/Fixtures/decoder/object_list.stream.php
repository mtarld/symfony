<?php

return static function (mixed $stream, \Psr\Container\ContainerInterface $denormalizers, \Symfony\Component\JsonEncoder\Decode\LazyInstantiator $instantiator, array $config): mixed {
    $providers['array<int,Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy>'] = static function ($stream, $offset, $length) use ($config, $denormalizers, $instantiator, &$providers) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitList($stream, $offset, $length);
        $iterable = static function ($stream, $data) use ($config, $denormalizers, $instantiator, &$providers) {
            foreach ($data as $k => $v) {
                yield $k => $providers['Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy']($stream, $v[0], $v[1]);
            }
        };
        return \iterator_to_array($iterable($stream, $data));
    };
    $providers['Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy'] = static function ($stream, $offset, $length) use ($config, $denormalizers, $instantiator, &$providers) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($data as $k => $v) {
            match ($k) {
                'id' => $properties['id'] = static function () use ($stream, $v, $config, $denormalizers, $instantiator, &$providers) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
                },
                'name' => $properties['name'] = static function () use ($stream, $v, $config, $denormalizers, $instantiator, &$providers) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    return $providers['array<int,Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy>']($stream, 0, null);
};
