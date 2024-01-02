<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy>|null'] = static function ($data) use($config, $instantiator, $services, &$providers) {
        if (null === $data) {
            return null;
        }
        $iterable = static function ($data) use($config, $instantiator, $services, &$providers) {
            foreach ($data as $k => $v) {
                (yield $k => $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy']($v));
            }
        };
        return \iterator_to_array($iterable($data));
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy'] = static function ($data) use($config, $instantiator, $services, &$providers) {
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy::class, \array_filter(['id' => $data['id'] ?? '_symfony_missing_value', 'name' => $data['name'] ?? '_symfony_missing_value'], static function ($v) {
            return '_symfony_missing_value' !== $v;
        }));
    };
    return $providers['array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\ClassicDummy>|null'](\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string, $flags));
};
