<?php

return static function (\Symfony\Component\Encoder\Stream\StreamReaderInterface&\Symfony\Component\Encoder\Stream\SeekableStreamInterface $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['?Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function (mixed $stream, int $offset, ?int $length) use($config, $instantiator, $services, &$providers, $flags) : ?\Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy {
        $boundaries = \Symfony\Component\Json\Template\Decode\Splitter::splitDict($stream, $offset, $length);
        if (null === $boundaries) {
            return null;
        }
        $properties = [];
        foreach ($boundaries as $k => $boundary) {
            if ('id' === $k) {
                $properties['id'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) : mixed {
                    return \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
            if ('name' === $k) {
                $properties['name'] = static function () use($stream, $boundary, $config, $instantiator, $services, &$providers, $flags) : mixed {
                    return \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags);
                };
                continue;
            }
        }
        return $instantiator->instantiate(\Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy::class, $properties);
    };
    return $providers['?Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy']($stream, 0, null);
};
