<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<int,mixed>'] = static function ($data) {
        return $data;
    };
    return $providers['array<int,mixed>'](\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string, $flags));
};
