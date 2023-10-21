<?php

return static function (\Symfony\Component\Encoder\Stream\StreamReaderInterface&\Symfony\Component\Encoder\Stream\SeekableStreamInterface $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['iterable<int,mixed>'] = static function (mixed $stream, int $offset, ?int $length) use($config, $instantiator, $services, &$providers, $flags) : iterable {
        $boundaries = \Symfony\Component\Json\Template\Decode\Splitter::splitList($stream, $offset, $length);
        $iterable = static function (mixed $stream, iterable $boundaries) use($config, $instantiator, $services, &$providers, $flags) : iterable {
            foreach ($boundaries as $k => $boundary) {
                (yield $k => \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags));
            }
        };
        return $iterable($stream, $boundaries);
    };
    return $providers['iterable<int,mixed>']($stream, 0, null);
};
