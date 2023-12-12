<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['iterable<string,mixed>'] = static function ($data) {
        return $data;
    };
    return $providers['iterable<string,mixed>'](\Symfony\Component\Json\Template\Decode\Decoder::decodeString($string, $flags));
};
