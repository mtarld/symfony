<?php

return static function (\Symfony\Component\JsonEncoder\Stream\StreamReaderInterface $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithOtherDummies'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\JsonEncoder\Template\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            match ($k) {
                'name' => $properties['name'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                'otherDummyOne' => $properties['otherDummyOne'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes']($stream, $boundary[0], $boundary[1]);
                },
                'otherDummyTwo' => $properties['otherDummyTwo'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy']($stream, $boundary[0], $boundary[1]);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithOtherDummies::class, $properties);
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\JsonEncoder\Template\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            match ($k) {
                '@id' => $properties['id'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                'name' => $properties['name'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes::class, $properties);
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\JsonEncoder\Template\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            match ($k) {
                'id' => $properties['id'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                'name' => $properties['name'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\JsonEncoder\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithOtherDummies']($stream, 0, null);
};
