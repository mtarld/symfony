<?php

return static function (string $string, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum'] = static function ($data) {
        return \Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum::from($data);
    };
    return $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Enum\\DummyBackedEnum'](\Symfony\Component\Json\Template\Decode\Decoder::decodeString($string, $flags));
};
