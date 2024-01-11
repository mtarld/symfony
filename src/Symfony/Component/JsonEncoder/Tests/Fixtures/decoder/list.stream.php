<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<int,mixed>'] = static function ($stream, $offset, $length) use($config, $instantiator, $services, &$providers, $flags) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitList($stream, $offset, $length);
        $iterable = static function ($stream, $data) use($config, $instantiator, $services, &$providers, $flags) {
            foreach ($data as $k => $v) {
                (yield $k => \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]));
            }
        };
        return \iterator_to_array($iterable($stream, $data));
    };
    return $providers['array<int,mixed>']($stream, 0, null);
};
