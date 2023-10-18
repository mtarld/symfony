<?php

/**
 * @return int
 */
return static function (\Symfony\Component\Encoder\Stream\StreamReaderInterface&\Symfony\Component\Encoder\Stream\SeekableStreamInterface $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $flags = $config["json_decode_flags"] ?? 0;
    $providers["int"] = static function (mixed $stream, int $offset, ?int $length) use ($flags): mixed {
        return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $offset, $length, $flags);
    };
    return ($providers["int"])($stream, 0, null);
};
