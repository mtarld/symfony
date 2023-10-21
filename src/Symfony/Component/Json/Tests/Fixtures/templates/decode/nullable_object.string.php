<?php

return static function (string $string, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['?Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($data) use($config, $instantiator, $services, &$providers) {
        if (null === $data) {
            return null;
        }
        return $instantiator->instantiate(\Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy::class, \array_filter(['id' => $data['id'] ?? '_symfony_missing_value', 'name' => $data['name'] ?? '_symfony_missing_value'], static function ($v) {
            return '_symfony_missing_value' !== $v;
        }));
    };
    return $providers['?Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy'](\Symfony\Component\Json\Template\Decode\Decoder::decodeString($string, $flags));
};
