<?php

return static function (mixed $stream, \Psr\Container\ContainerInterface $denormalizers, \Symfony\Component\JsonEncoder\Decode\LazyInstantiator $instantiator, array $config): mixed {
    $providers['array<string,mixed>'] = static function ($stream, $offset, $length) use ($config, $denormalizers, $instantiator, &$providers) {
        $data = \Symfony\Component\JsonEncoder\Decode\Splitter::splitDict($stream, $offset, $length);
        $iterable = static function ($stream, $data) use ($config, $denormalizers, $instantiator, &$providers) {
            foreach ($data as $k => $v) {
                yield $k => \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $v[0], $v[1]);
            }
        };
        return \iterator_to_array($iterable($stream, $data));
    };
    return $providers['array<string,mixed>']($stream, 0, null);
};
