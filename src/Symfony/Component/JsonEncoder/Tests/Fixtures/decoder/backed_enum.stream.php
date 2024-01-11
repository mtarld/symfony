<?php

return static function (mixed $stream, array $config, \Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface $instantiator, ?\Psr\Container\ContainerInterface $services) : mixed {
    $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum'] = static function ($stream, $offset, $length) {
        return \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum::from(\Symfony\Component\JsonEncoder\Decode\NativeDecoder::decodeStream($stream, $offset, $length));
    };
    return $providers['Symfony\\Component\\JsonEncoder\\Tests\\Fixtures\\Enum\\DummyBackedEnum']($stream, 0, null);
};
