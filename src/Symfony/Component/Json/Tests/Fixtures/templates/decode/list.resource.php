<?php

return static function (mixed $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<int,mixed>'] = static function (mixed $stream, int $offset, ?int $length) use($config, $instantiator, $services, &$providers, $flags) : array {
        $boundaries = \Symfony\Component\Json\Template\Decode\Splitter::splitList($stream, $offset, $length);
        $iterable = static function (mixed $stream, iterable $boundaries) use($config, $instantiator, $services, &$providers, $flags) : iterable {
            foreach ($boundaries as $k => $boundary) {
                (yield $k => \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags));
            }
        };
        return \iterator_to_array($iterable($stream, $boundaries));
    };
    return $providers['array<int,mixed>']($stream, 0, null);
};
