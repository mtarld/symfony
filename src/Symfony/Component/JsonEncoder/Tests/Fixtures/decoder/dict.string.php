<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string);
};
