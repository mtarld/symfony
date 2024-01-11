<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum>'] = static function ($data) use($config, $instantiator, $services, &$providers, $flags) {
        $iterable = static function ($data) use($config, $instantiator, $services, &$providers, $flags) {
            foreach ($data as $k => $v) {
                (yield $k => $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum']($v));
            }
        };
        return \iterator_to_array($iterable($data));
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum'] = static function ($data) use($flags) {
        return \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum::from($data);
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes'] = static function ($data) use($config, $instantiator, $services, &$providers, $flags) {
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes::class, \array_filter(['id' => $data['@id'] ?? '_symfony_missing_value', 'name' => $data['name'] ?? '_symfony_missing_value'], static function ($v) {
            return '_symfony_missing_value' !== $v;
        }));
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes|array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum>|int'] = static function ($data) use($config, $instantiator, $services, &$providers, $flags) {
        if (\is_array($data) && \array_is_list($data)) {
            return $providers['array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum>']($data);
        }
        if (\is_array($data)) {
            return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes']($data);
        }
        if (\is_int($data)) {
            return $data;
        }
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value for "Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes|array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum>|int".', \get_debug_type($data)));
    };
    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithNameAttributes|array<int,Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum>|int'](\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string, $flags));
};
