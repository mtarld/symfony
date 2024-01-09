<?php

return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services) : \Traversable {
    $flags = $config['json_encode_flags'] ?? 0;
    (yield '{"value":');
    if ($data->value instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum) {
        (yield \json_encode($data->value->value, $flags));
    } elseif (null === $data->value) {
        (yield 'null');
    } elseif (\is_string($data->value)) {
        (yield \json_encode($data->value, $flags));
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data->value)));
    }
    (yield '}');
};
