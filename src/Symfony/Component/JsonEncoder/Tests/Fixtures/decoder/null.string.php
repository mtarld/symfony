<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['null'] = static function ($data) {
        return $data;
    };
    return $providers['null'](\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string, $flags));
};
