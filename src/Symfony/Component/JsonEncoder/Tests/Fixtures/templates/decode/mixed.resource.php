<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['mixed'] = static function ($stream, $offset, $length) use($flags) {
        return \Symfony\Component\JsonEncoder\Template\Decode\Decoder::decodeStream($stream, $offset, $length, $flags);
    };
    return $providers['mixed']($stream, 0, null);
};
