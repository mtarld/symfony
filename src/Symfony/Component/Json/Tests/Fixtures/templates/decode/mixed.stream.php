<?php

return static function (\Symfony\Component\Encoder\Stream\StreamReaderInterface&\Symfony\Component\Encoder\Stream\SeekableStreamInterface $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['mixed'] = static function ($stream, $offset, $length) use($flags) {
        return \Symfony\Component\Json\Template\Decode\Decoder::decodeStream($stream, $offset, $length, $flags);
    };
    return $providers['mixed']($stream, 0, null);
};
