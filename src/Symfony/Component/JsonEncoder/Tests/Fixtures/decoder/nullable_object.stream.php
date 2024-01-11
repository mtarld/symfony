<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($data as $k => $v) {
            match ($k) {
                'id' => $properties['id'] = static function () use($stream, $v, $config, $instantiator, $services, &$providers) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
                },
                'name' => $properties['name'] = static function () use($stream, $v, $config, $instantiator, $services, &$providers) {
                    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy|null'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers) {
        $data = \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $offset, $length);
        if (\is_array($data)) {
            return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy']($data);
        }
        if (null === $data) {
            return null;
        }
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value for "Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy|null".', \get_debug_type($data)));
    };
    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy|null']($stream, 0, null);
};
