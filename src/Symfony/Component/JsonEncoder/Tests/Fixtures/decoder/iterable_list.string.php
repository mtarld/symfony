<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Decode\Instantiator $instantiator, ?\Psr\Container\ContainerInterface $services): mixed {
    return \Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string);
};
