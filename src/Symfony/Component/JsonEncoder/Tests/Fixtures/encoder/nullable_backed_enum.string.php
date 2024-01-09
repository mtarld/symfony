<?php

return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services) : \Traversable {
    $flags = $config['json_encode_flags'] ?? 0;
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum) {
        (yield \json_encode($data->value, $flags));
    } elseif (null === $data) {
        (yield 'null');
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
