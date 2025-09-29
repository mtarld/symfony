<?php

/**
 * @return Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithNullableProperties
 */
return static function (string|\Stringable $string, \Psr\Container\ContainerInterface $valueTransformers, \Symfony\Component\JsonStreamer\Read\Instantiator $instantiator, array $options): mixed {
    $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithNullableProperties'] = static function ($data) use ($options, $valueTransformers, $instantiator, &$providers) {
        return $instantiator->instantiate(\Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithNullableProperties::class, \array_filter(['name' => $data['name'] ?? '_symfony_missing_value', 'enum' => \array_key_exists('enum', $data) ? $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Enum\DummyBackedEnum|null']($data['enum']) : '_symfony_missing_value'], static function ($v) {
            return '_symfony_missing_value' !== $v;
        }));
    };
    $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Enum\DummyBackedEnum'] = static function ($data) {
        return \Symfony\Component\JsonStreamer\Tests\Fixtures\Enum\DummyBackedEnum::from($data);
    };
    $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Enum\DummyBackedEnum|null'] = static function ($data) use ($options, $valueTransformers, $instantiator, &$providers) {
        if (null === $data) {
            return null;
        }
        return $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Enum\DummyBackedEnum']($data);
    };
    return $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithNullableProperties'](\Symfony\Component\JsonStreamer\Read\Decoder::decodeString((string) $string));
};
