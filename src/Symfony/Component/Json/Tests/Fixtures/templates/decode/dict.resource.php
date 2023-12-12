<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<string,mixed>'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $boundaries = \Symfony\Component\Json\Template\Decode\Splitter::splitDict($stream, $offset, $length);
        $iterable = static function ($stream, $boundaries) use($config, $instantiator, $services, &$providers, $flags) {
            foreach ($boundaries as $k => $boundary) {
                (yield $k => \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $boundary[0], $boundary[1], $flags));
            }
        };
        return \iterator_to_array($iterable($stream, $boundaries));
    };
    return $providers['array<string,mixed>']($stream, 0, null);
};
