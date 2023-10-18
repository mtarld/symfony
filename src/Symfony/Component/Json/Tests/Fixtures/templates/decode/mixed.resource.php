<?php

/**
 * @param resource $stream
 * @return mixed
 */
return static function (mixed $stream, array $config, \Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    $flags = $config["json_decode_flags"] ?? 0;
    $providers["mixed"] = static function (mixed $stream, int $offset, ?int $length) use ($flags): mixed {
        return "\\Symfony\\Component\\Json\\Template\\Decode\\Decoder"::decodeStream($stream, $offset, $length, $flags);
    };
    return ($providers["mixed"])($stream, 0, null);
};
