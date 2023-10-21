<?php

return static function (string $string, array $config, \Symfony\Component\Encoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies'] = static function (?array $data) use($config, $instantiator, $services, &$providers) : \Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies {
        return $instantiator->instantiate(\Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies::class, \array_filter(['name' => $data['name'] ?? '_symfony_missing_value', 'otherDummyOne' => \array_key_exists('otherDummyOne', $data) ? $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes']($data['otherDummyOne']) : '_symfony_missing_value', 'otherDummyTwo' => \array_key_exists('otherDummyTwo', $data) ? $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy']($data['otherDummyTwo']) : '_symfony_missing_value'], static function (mixed $v) : bool {
            return '_symfony_missing_value' !== $v;
        }));
    };
    $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithNameAttributes'] = static function (?array $data) use($config, $instantiator, $services, &$providers) : \Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes {
        return $instantiator->instantiate(\Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes::class, \array_filter(['id' => $data['@id'] ?? '_symfony_missing_value', 'name' => $data['name'] ?? '_symfony_missing_value'], static function (mixed $v) : bool {
            return '_symfony_missing_value' !== $v;
        }));
    };
    $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function (?array $data) use($config, $instantiator, $services, &$providers) : \Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy {
        return $instantiator->instantiate(\Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy::class, \array_filter(['id' => $data['id'] ?? '_symfony_missing_value', 'name' => $data['name'] ?? '_symfony_missing_value'], static function (mixed $v) : bool {
            return '_symfony_missing_value' !== $v;
        }));
    };
    return $providers['Symfony\\Component\\Json\\Tests\\Fixtures\\Model\\DummyWithOtherDummies'](\Symfony\Component\Json\Template\Decode\Decoder::decodeString($string, $flags));
};
