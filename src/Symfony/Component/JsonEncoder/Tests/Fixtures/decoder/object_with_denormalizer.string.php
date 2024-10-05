<?php

return static function (string|\Stringable $string, \Psr\Container\ContainerInterface $denormalizers, \Symfony\Component\JsonEncoder\Decode\Instantiator $instantiator, array $config): mixed {
    $providers['Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNormalizerAttributes'] = static function ($data) use ($config, $denormalizers, $instantiator, &$providers) {
        return $instantiator->instantiate(\Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNormalizerAttributes::class, \array_filter(['id' => $denormalizers->get('Symfony\Component\JsonEncoder\Tests\Fixtures\Denormalizer\DivideStringAndCastToIntDenormalizer')->denormalize($data['id'] ?? '_symfony_missing_value', $config), 'active' => $denormalizers->get('Symfony\Component\JsonEncoder\Tests\Fixtures\Denormalizer\BooleanStringDenormalizer')->denormalize($data['active'] ?? '_symfony_missing_value', $config)], static function ($v) {
            return '_symfony_missing_value' !== $v;
        }));
    };
    return $providers['Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNormalizerAttributes'](\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeString((string) $string));
};
