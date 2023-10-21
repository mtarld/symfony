<?php

return static function (mixed $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<int,Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy>'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\Json\Template\Decode\Splitter::splitList($stream, $offset, $length);
        $iterable = static function ($stream, $boundaries) use($config, $instantiator, $services, &$providers, $flags) {
            foreach ($boundaries as $k => $boundary) {
                (yield $k => $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy']($stream, $boundary[0], $boundary[1]));
            }
        };
        return \iterator_to_array($iterable($stream, $boundaries));
    };
    $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\Json\Template\Decode\Splitter::splitDict($stream, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            match ($k) {
                'id' => $properties['id'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                'name' => $properties['name'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) {
                    return \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                },
                default => null,
            };
        }
        return $instantiator->instantiate(\Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    return $providers['array<int,Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy>']($stream, 0, null);
};
