<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, 0, null);
};
