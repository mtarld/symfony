<?php

return static function (\Symfony\Component\JsonEncoder\Stream\StreamReaderInterface $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['int'] = static function ($stream, $offset, $length) use($flags) {
        return \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $offset, $length, $flags);
    };
    return $providers['int']($stream, 0, null);
};
