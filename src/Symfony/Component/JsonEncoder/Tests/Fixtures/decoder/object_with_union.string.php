<?php

return static function (string $string, array $config, \Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $flags = $config['json_decode_flags'] ?? 0;
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithUnionProperties'] = static function ($data) use($config, $instantiator, $services, &$providers, $flags) {
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithUnionProperties::class, \array_filter(['value' => \array_key_exists('value', $data) ? $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum|null|string']($data['value']) : '_symfony_missing_value'], static function ($v) {
            return '_symfony_missing_value' !== $v;
        }));
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum'] = static function ($data) use($flags) {
        return \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum::from($data);
    };
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum|null|string'] = static function ($data) use($config, $instantiator, $services, &$providers, $flags) {
        if (\is_int($data)) {
            return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum']($data);
        }
        if (null === $data) {
            return null;
        }
        if (\is_string($data)) {
            return $data;
        }
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value for "Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum|null|string".', \get_debug_type($data)));
    };
    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Model\\DummyWithUnionProperties'](\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString($string, $flags));
};
